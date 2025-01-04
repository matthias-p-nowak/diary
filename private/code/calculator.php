<?php

namespace Code;

class Calculator
{
    /**
     * @var array<<missing>,mixed>
     */
    private array $activities = [];
    private array $children = [];

    private array $levels = [];

    /**
     * @return void
     */
    public function calculate(): void
    {
        error_log(__FILE__ . ':' . __LINE__ . ' ' . __FUNCTION__ . ' calculating...');
        $db = Db\DbCtx::getCtx();
        $sql = <<< 'EOS'
        -- get ended
        UPDATE `${prefix}Event` e JOIN (
            SELECT `Started`, LAG(`Started`) OVER( ORDER BY `Started` DESC ) AS 'Ended' FROM `${prefix}Event`
            ) c on e.`Started`=c.`Started`
         set e.`Ended`=c.`Ended`;
        -- summarize work
        REPLACE INTO `${prefix}Accounted` (`Activity`, `Day`, `YearWeek`, `WeekDay`, `Sofar` )
        WITH RECURSIVE CTE_1 AS ( SELECT `Activity`, `Activity` AS 'Parent', 0 AS 'Src'
            FROM `${prefix}Event`
            UNION ALL
            SELECT a.`Activity`, c.`Parent`, c.`Src` +1 AS 'Src'
            FROM `${prefix}Activity` AS a
            JOIN `CTE_1` AS c ON a.`Parent` = c.`Activity`
            WHERE a.`Results` = 1 )
        , CTE_2 AS ( SELECT c.`Activity`, c.`Parent`, c.`Src`,
            ROW_NUMBER() OVER (PARTITION BY c.`Activity`, c.`Parent` order by c.`Src`) as 'RN'
            FROM CTE_1 c )
        , CTE_3 AS ( SELECT c.`Parent`, e.`Activity`, e.`Started`, c.`Src`,
            TIMESTAMPDIFF(Second, e.`Started`, e.`Ended`) / 3600 AS 'hours',
            TO_DAYS(e.`Started`) AS 'day',
            YEARWEEK(e.`Started`,3) AS 'yearweek',
            WEEKDAY(e.`Started`) AS 'weekday'
            FROM `${prefix}Event` AS e
            JOIN CTE_2 AS c on c.`Activity`=e.`Activity`
            WHERE c.RN = 1 )
        , CTE_4 AS ( SELECT `Started`, `Src`, `Parent` as 'Activity', `day`, `yearweek`, `weekday`,
            sum(`hours`) over (Partition by `Parent`, `day`) as 'Sofar' FROM CTE_3
            WHERE `hours` is not null )
        SELECT `Activity`, `day`, `yearweek`, `weekday`, `Sofar` from  CTE_4;
        -- making accounted
        UPDATE `${prefix}Accounted` SET `Accounted`=CEIL(`Sofar`*2)/2;
        -- getting day accounts
        update `${prefix}Accounted` a JOIN (
            SELECT `Activity`, `Day`, LAG(`Accounted`) OVER (PARTITION BY `Activity` order by `Day`) as 'PreviousAccount'
            from `${prefix}Accounted` ) sq ON a.`Activity`=sq.`Activity` and a.`Day`=sq.`Day`
        set a.`DayAccount`= COALESCE(a.`Accounted`-sq.`PreviousAccount`, a.`Accounted`);
        EOS;
        $res = $db->query($sql);

    }
    /**
     * @return void
     */
    public function getHierarchy(): void
    {
        $db = Db\DbCtx::getCtx();
        $activities = 0;
        foreach ($db->findRows('Activity') as $row) {
            $activities += 1;
            $this->activities[$row->Activity] = $row;
            if (!is_null($row->Parent)) {
                $this->children[$row->Parent][$row->Activity] = $row;
            }
        }
        $this->levels = [];
        do {
            $again = false;
            foreach ($this->children as $parent => $crow) {
                foreach ($crow as $child => $row) {
                    $cl = $this->levels[$child] ?? 0;
                    $pl = $this->levels[$parent] ?? 0;
                    if ($cl < $pl + 1) {
                        $this->levels[$child] = $pl + 1;
                        $again = true;
                    }
                }
            }
        } while ($again);
    }

}

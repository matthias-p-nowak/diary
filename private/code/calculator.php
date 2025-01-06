<?php

namespace Code;

class Calculator
{
    /**
     * @var array<<missing>,mixed>
     */
    public array $activities = [];
    public array $children = [];

    public array $levels = [];

    /**
     * @return void
     */
    public function calculate(): void
    {
        error_log(__FILE__ . ':' . __LINE__ . ' ' . __FUNCTION__ . ' calculating...');
        $db = Db\DbCtx::getCtx();
        $sql = <<< 'EOS'
        -- get ended
        UPDATE ${prefix}Event e JOIN (
            SELECT Started, LAG(Started) OVER( ORDER BY Started DESC ) AS 'Ended' FROM ${prefix}Event
            ) c on e.Started=c.Started
         set e.Ended=c.Ended;
        TRUNCATE ${prefix}Accounted;
        -- summarize work
        INSERT INTO `${prefix}Accounted` (`Activity`, `Day`, `YearWeek`, `WeekDay`, `Sofar` )
        WITH RECURSIVE CTE_1 AS (
            SELECT e.Activity, e.Activity AS 'Parent', 0 AS 'Src'
            FROM ${prefix}Event e JOIN ${prefix}Activity a on e.Activity=a.Activity
            WHERE a.Results=1
            UNION ALL
            SELECT a.Activity, c.Parent, c.Src +1 AS 'Src'
            FROM ${prefix}Activity AS a
            JOIN CTE_1 AS c ON a.Parent = c.Activity
            WHERE a.Results = 1 )
        , CTE_2 AS (
            SELECT c.Activity, c.Parent, c.Src,
            ROW_NUMBER() OVER (PARTITION BY c.Activity, c.Parent order by c.Src) as 'RN'
            FROM CTE_1 c )
        , CTE_3 AS (
            SELECT 
            c.Parent, e.Activity, e.Started, c.Src,
            TIMESTAMPDIFF(Second, e.Started, e.Ended) / 3600 AS 'hours',
            TO_DAYS(e.started) AS 'day',
            YEARWEEK(e.Started,3) AS 'yearweek',
            WEEKDAY(e.Started) AS 'weekday'
            FROM ${prefix}Event AS e 
            JOIN CTE_2 AS c on c.Activity=e.Activity
            WHERE c.RN = 1 )
        , CTE_4 AS (
            SELECT `Started`, `Src`, `Activity`, `day`, `yearweek`, `weekday`, `Parent`,
            sum(hours) over (Partition by `Parent` ORDER BY `Started`) as 'Sofar' 
            FROM CTE_3 WHERE `hours` is not null )
        , CTE_5 AS (
            SELECT `Parent` AS 'Activity', `day`, `yearweek`, `weekday`,
            MAX(`Sofar`) as 'Sofar'
            FROM CTE_4
            GROUP BY `Parent`, `day`, `yearweek`, `weekday` )
        SELECT `Activity`, `day`, `yearweek`, `weekday`, `Sofar` from  CTE_5;
        -- making accounted
        UPDATE `${prefix}Accounted` SET `Accounted`=CEIL(`Sofar`*2)/2; 
        -- getting day accounts
        update `${prefix}Accounted` a JOIN (
            SELECT `Activity`, `Day`, LAG(`Accounted`) OVER (PARTITION BY `Activity` order by `Day`) as 'PreviousAccount'
            from `${prefix}Accounted` 
        ) sq ON a.`Activity`=sq.`Activity` and a.`Day`=sq.`Day`
        set a.`DayAccount`= COALESCE(a.`Accounted`-sq.`PreviousAccount`, a.`Accounted`);
        EOS;
        $res = $db->query($sql);
    }
    
    /**
     * @return void
     */
    public function calcHierarchy(): void
    {
        $db = Db\DbCtx::getCtx();
        $activities = 0;
        $this->levels = [];
        foreach ($db->findRows('Activity') as $row) {
            $activities += 1;
            $this->levels[$row->Activity] = 0;
            $this->activities[$row->Activity] = $row;
            if (!is_null($row->Parent)) {
                $this->children[$row->Parent][$row->Activity] = $row;
                $this->levels[$row->Parent] = 0;
            }
        }
        AgainFromScratch:
        foreach ($this->activities as $act => $row) {
            $this->levels[$act] = 0;
        }
        do {
            $again = false;
            foreach ($this->children as $parent => $crow) {
                foreach ($crow as $child => $row) {
                    $cl = $this->levels[$child] ?? 0;
                    $pl = $this->levels[$parent] ?? 0;
                    if ($pl > $activities) {
                        $db->deleteRow($row);
                        unset($this->children[$parent][$child]);
                        goto AgainFromScratch;
                    } else {
                        if ($cl < $pl + 1) {
                            $this->levels[$child] = $pl + 1;
                            $again = true;
                        }
                    }
                }
            }
        } while ($again);
    }
    /**
     * @return void
     * @param mixed $activity
     */
    public function showTree($activity): void
    {
        $line=$activity;
        if(isset($this->activities[$activity])){
            if($this->activities[$activity]->Results){
                $line='<b>'.$line.'</b>';
            }
        }else{
            $line='<em>'.$line.'</em>';
        }
        echo '<div>'.$line;
        foreach(($this->children[$activity] ?? []) as $child => $row){
            $this->showTree($child);
        }
        echo '</div>';
    }
}

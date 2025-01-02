<?php

namespace Code;

class Page
{

    /**
     * @return void
     */
    public function showHome(): void
    {
        global $status;
        $cssFile = \join(DIRECTORY_SEPARATOR, [__DIR__, 'assets', 'main.css']);
        $cssContent = \file_get_contents($cssFile);
        $scripts = ['htmx-lite.js', 'diary.js'];
        $scriptContent = '';
        foreach ($scripts as $script) {
            $scriptFile = join(DIRECTORY_SEPARATOR, [__DIR__, 'assets', $script]);
            $scriptContent .= \file_get_contents($scriptFile);
        }
        echo <<< EOM
        <!DOCTYPE html>
        <html><head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta charset="utf-8">
        <link rel="icon" type="image/x-icon" href="diary.ico">
        <style>$cssContent</style>
        <script>$scriptContent</script>
        </head>
        <body>
        <h1>Diary</h1>
        EOM;
        $this->showTopBox();
        $this->showMain();
        echo <<< EOM
        </body>
        </html>
        EOM;
    }

    /**
     * @return void
     */
    private function showTopBox(): void
    {
        global $status;
        $scriptURL = $_SERVER['SCRIPT_NAME'];
        $sameStyle = '';
        $editStyle = '';
        $resultStyle = '';
        $configStyle = '';
        match ($status->mode ?? '') {
            'edit' => $editStyle = 'style="background-color: yellow;"',
            'result' => $resultStyle = 'style="background-color: aqua;"',
            'config' => $configStyle = 'style="background-color: bisque"',
            default => $sameStyle = 'style="background-color: lightgreen;"',
        };
        $lt = '<!-- ' . __FILE__ . ':' . __LINE__ . ' ' . ' -->';
        echo <<< EOM
        <div id="topbox" x-action="replace" >
        $lt
        <form action="$scriptURL/change_mode" onsubmit="return false;" onclick="hxl_submit_form(event);">
        <span name="same" $sameStyle >Same</span>
        <span name="edit" $editStyle >Edit</span>
        <span name="result" $resultStyle >Results</span>
        <span name="config" $configStyle >Configure</span>
        </form>
        </div>
        EOM;
    }

    /**
     * @return void
     */
    private function events2Register(): void
    {
        $scriptURL = $_SERVER['SCRIPT_NAME'];
        $lt = '<!-- ' . __FILE__ . ':' . __LINE__ . ' ' . ' -->';
        echo <<< EOM
        <div id="main" x-action="replace">
        $lt
        <h2>Past events</h2>
        <form action="$scriptURL/same_event" onsubmit="return false;" onclick="register_event(event);">
        EOM;
        $this->showAllActivities();
        echo <<< EOM
        </form>
        </div>
        EOM;
    }

    /**
     * @return void
     */
    public static function Show_Home(): void
    {
        $p = new Page();
        $p->showHome();
    }

    /**
     * @return void
     */
    public static function Change_Mode(): void
    {
        global $status;
        if (isset($_POST['name'])) {
            $status->mode = $_POST['name'];
            $p = new Page();
            $p->showTopBox();
            $p->showMain();
        } else {
            http_response_code(400);
            echo <<< EOM
            something wrong with the request
            EOM;
        }
    }

    /**
     * @return void
     */
    public function editEvent(mixed $ev): void
    {
        $scriptURL = $_SERVER['SCRIPT_NAME'];
        $lt = '<!-- ' . __FILE__ . ':' . __LINE__ . ' ' . ' -->';
        echo <<< EOM
        <div id="main" x-action="replace">
        $lt
        <h2>Editing</h2>
        <div class="table">
        <form action="$scriptURL/edit_event" onsubmit="return false;" >
        <input type="hidden" name="id" value="{$ev->Id}">
        <div class="row">
        <span class="right">Activity</span>
        <input type="text" name="activity" class="wide" onchange="hxl_submit_form(event);"
            value="{$ev->Activity}" placeholder="name for activity" >
        </div>
        <div class="row">
        <span class="right">Details</span>
        <input type="text" name="details" class="wide" onchange="hxl_submit_form(event);"
            value="{$ev->Details}" placeholder="What will be done this time?" >
         </div>
          <div class="row">
         <span class="right">Started</span>
        <input type="text" name="started" class="wide" onchange="hxl_submit_form(event);"
            value="{$ev->Started}" placeholder="start time" >
         </div>
         <div class="row">
         <span class="right">Remote IP</span><span>{$ev->IP}</span>
         </div>
         <div class="row">
         <span>Lat: {$ev->Latitude}</span><span>Lon: {$ev->Longitude}</span>
         </div>
         <div class="row"><span></span><span><input type="button" name="delete" value="Delete" onclick="hxl_submit_form(event);" ></span></div>
         </form>
        </div>
        </div>
        EOM;
    }
    /**
     * @return void
     */
    private function showDefaultMain(): void
    {
        $lt = '<!-- ' . __FILE__ . ':' . __LINE__ . ' ' . ' -->';
        echo <<< EOM
        <div id="main" x-action="replace">
        <h3>Nothing to show</h3>
        $lt
        </div>
        EOM;
    }
    /**
     * @return void
     */
    private function events2Edit(): void
    {
        global $status;
        $lt = '<!-- ' . __FILE__ . ':' . __LINE__ . ' ' . ' -->';
        $scriptURL = $_SERVER['SCRIPT_NAME'];
        unset($status->lastShown);
        echo <<< EOM
        <div id="main" x-action="replace">
        <h2>Recent events</h2>
        <div id="recents" class="table" >
        $lt
        </div>
        <div id="sentinel" action="$scriptURL/more_events">more...</div>
        </div>
        <script>watch4moreEdits();</script>
        EOM;
    }
    /**
     * @return void
     */
    private function showMain(): void
    {
        global $status;
        $status->mode ??= 'same';
        match ($status->mode) {
            'same' => $this->events2Register(),
            'edit' => $this->events2Edit(),
            'config' => $this->activities2Configure(),
            'result' => $this->presentResults(),
            default => $this->showDefaultMain(),
        };
    }
    /**
     * @return void
     */
    public static function More_Events(): void
    {
        global $status;
        $scriptURL = $_SERVER['SCRIPT_NAME'];
        $ls = $status->lastShown ?? ((new \DateTime())->format('y-m-d H:i:s'));
        error_log(__FILE__ . ':' . __LINE__ . ' ' . __FUNCTION__ . ' more event...');
        $db = Db\DbCtx::getCtx();
        $m = 0;
        $sql = <<< 'EOS'
        SELECT * FROM `${prefix}Event` WHERE Started < :st ORDER BY Started DESC limit 5;
        EOS;
        $evs = $db->sqlAndRows($sql, 'Event', ['st' => $ls]);
        echo <<< EOM
        <div id="sentinel" x-action="remove">removing</div>
        EOM;
        $lt = '<!-- ' . __FILE__ . ':' . __LINE__ . ' ' . ' -->';
        foreach ($evs as $ev) {
            $status->lastShown = $ev->Started;
            echo <<< EOM
            <form  action="$scriptURL/show_event" class="row"
                onsubmit="return false;" onclick="hxl_submit_form(event);"
                x-action="append" x-id="recents">
            $lt
            <input type="hidden" name="id" value="{$ev->Id}">
            <span onclick="hxl_submit_form(event);">{$ev->Started}</span><span>{$ev->Activity}</span><span>{$ev->Details}</span>
            </form>
            EOM;
            $m += 1;
        }
        echo <<<EOM
        EOM;
        if ($m > 0) {
            echo <<< EOM
            <div id="sentinel" action="$scriptURL/more_events" x-action="append" x-id="main">more...</div>
            <script>watch4moreEdits();</script>
            EOM;
        } else {
            $lt = '<!-- ' . __FILE__ . ':' . __LINE__ . ' ' . ' -->';
            echo <<< EOM
            <div x-action="append" x-id="main">$lt all events shown</div>
            EOM;
        }
    }
    /**
     * @return void
     */
    public static function Show_Event(): void
    {
        $db = Db\DbCtx::getCtx();
        $id = $_POST["id"];
        $evs = $db->findRows('Event', ['Id' => $id]);
        $ev = $evs->current();
        $p = new Page();
        $p->editEvent($ev);
    }
    /**
     * @return void
     */
    public function activities2Configure(): void
    {
        $scriptURL = $_SERVER['SCRIPT_NAME'];
        $lt = '<!-- ' . __FILE__ . ':' . __LINE__ . ' ' . ' -->';
        echo <<< EOM
        <div id="main" x-action="replace">
        $lt
        <h2>Activities</h2>
        <form action="$scriptURL/show_activity" onsubmit="return false;" onclick="hxl_submit_form(event);">
        EOM;
        $this->showAllActivities();
        echo <<< EOM
        </form>
        </div>
        EOM;

    }
    /**
     * @return void
     */
    private function showAllActivities(): void
    {
        $db = Db\DbCtx::getCtx();
        echo '<div class="boxed">';
        $sql = <<< 'EOS'
        WITH C_RN AS (
        SELECT *, ROW_NUMBER() OVER (PARTITION BY Activity order by Started DESC) AS RN FROM ${prefix}Event
        ) SELECT * from C_RN WHERE RN=1 ORDER By Started DESC
        EOS;
        foreach ($db->sqlAndRows($sql, 'Event') as $ev) {
            echo <<< EOM
            <div id="{$ev->Id}" name="{$ev->Activity}">{$ev->Activity}</div>
            EOM;
        }
        echo '</div>';
    }

    /**
     * @return void
     * @param string $activity
     */
    public static function Show_Activity(string $activity): void
    {
        error_log(__FILE__ . ':' . __LINE__ . ' ' . __FUNCTION__);
        $scriptURL = $_SERVER['SCRIPT_NAME'];
        // $activity=$_POST["name"];
        $db = Db\DbCtx::getCtx();
        $actRow = $db->findRows('Activity', ["Activity" => $activity])->current();
        if (is_null($actRow)) {
            $actRow = new Db\Activity();
            $actRow->Activity = $activity;
            $db->storeRow($actRow);
        }
        $cbChecked = $actRow->Results ? 'checked' : '';
        $activities = $db->query('SELECT DISTINCT Activity FROM `${prefix}Event` order by Started desc');
        $activities = array_map(fn($it) => $it[0], $activities);
        $lt = '<!-- ' . __FILE__ . ':' . __LINE__ . ' ' . ' -->';
        echo <<< EOM
        <div id="main" x-action="replace">
        $lt
        <h2>Activity</h2>
        <form action="$scriptURL/edit_activity" onsubmit="return false;" >
        <input type="hidden" name="original" value="$activity">
        <div class="table">
        <div class="row">
        <label>Activity</label>
        <input type="text" name="activity" value="$activity" placeholder="name for activity" onchange="hxl_submit_form(event);" >
        </div>
        <div class="row">
        <span>Time sheet</span>
        <label for="results">
        <input type="checkbox" name="results" id="results" $cbChecked onchange="hxl_submit_form(event);">
        <span>create results</span>
        </label>
        </div>
        <div class="row">
        <label for="sel_parent">Label</label>
        <span>
        <select name="sel_parent" id="sel_parent" onchange="hxl_submit_form(event);" >
        EOM;
        foreach ($activities as $act) {
            $selected = $act == $actRow->Parent ? ' selected ' : '';
            echo <<< EOM
            <option value="$act" $selected>$act</option>
            EOM;
        }
        $selected = is_null($actRow->Parent) ? 'selected' : '';
        echo <<< EOM
        <option value="" $selected>-</option>
        </select>
        </span>
        </div>
        <div class="row">
            <span></span>
            <span><input type="button" name="delete" value="Delete" onclick="hxl_submit_form(event);"></span>
        </div>
        </div>
        </form>
        </div>
        EOM;
    }
    /**
     * @return void
     */
    private function presentResults(): void
    {
        error_log(__FILE__ . ':' . __LINE__ . ' ' . __FUNCTION__ . ' calculating...');
        $calc = new Calculator();
        $calc->calculate();
        $db = Db\DbCtx::getCtx();
        $acc = $db->findRows('Accounted',[], 'ORDER BY `YearWeek`, `Activity`');
        $lt = '<!-- ' . __FILE__ . ':' . __LINE__ . ' ' . ' -->';
        echo <<< EOM
        <div id="main" x-action="replace">
        $lt
        <h2>Results</h2>
        </div>
        EOM;
    }
}

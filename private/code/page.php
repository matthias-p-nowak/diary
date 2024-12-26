<?php

namespace Code;

class Page
{

    /**
     * @return void
     */
    public function showHome(): void
    {
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
        $this->showEvents();
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
        match ($status->mode) {
            'edit' => $editStyle = 'style="background-color: yellow;"',
            'result' => $resultStyle = 'style="background-color: aqua;"',
            'config' => $configStyle = 'style="background-color: bisque"',
            default => $sameStyle = 'style="background-color: lightgreen;"',
        };
        echo <<< EOM
        <div id="topbox" x-action="replace" >
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
    private function showEvents(): void
    {
        $scriptURL = $_SERVER['SCRIPT_NAME'];
        echo <<< EOM
        <div id="main" x-action="replace">
        <h2>Past events</h2>
        <div class="boxed">
        <form action="$scriptURL/event" onsubmit="return false;" onclick="register_event(event);">
        <div id="-1">New event</div>
        </form>
        </div>
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
        echo <<< EOM
        <div id="main" x-action="replace">
        <h2>Editing</h2>
        <div class="table">
        <form action="$scriptURL/edit_activity" onsubmit="return false;" >
        <input type="hidden" value="{$ev->Id}">
        <div class="row">
        <span class="right">Activity</span>
        <span name="activity" class="wide" contenteditable onchange="hxl_submit_form(event);" >{$ev->Activity}</span>
         </div>
         <div class="row">
         <span class="right">Details</span>
         <span name="details" class="wide" contenteditable onchange="hxl_submit_form(event);" >{$ev->Details}</span>
         </div>
          <div class="row">
         <span class="right">Started</span>
         <span name="started" class="wide" contenteditable onchange="hxl_submit_form(event);" >{$ev->Started}</span>
         </div>
        </form>
        </div>
        </div>
        EOM;
    }
}

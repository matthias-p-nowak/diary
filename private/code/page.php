<?php

namespace Code;

class Page{

    public function showHome()
    {
        $cssFile=\join(DIRECTORY_SEPARATOR, [__DIR__, 'assets','main.css']);
        $cssContent=\file_get_contents($cssFile);
        $scripts=['htmx-lite.js', 'diary.js'];
        $scriptContent='';
        foreach($scripts as $script){
            $scriptFile=join(DIRECTORY_SEPARATOR, [__DIR__, 'assets',$script]);
            $scriptContent .= \file_get_contents($scriptFile);
        }
        echo <<< EOM
        <!DOCTYPE html>
        <html><head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <meta charset="utf-8">
        <link rel="icon" type="image/x-icon" href="diary.ico">
        <style>$cssContent</style>
        <script>$scriptContent</script>
        </head>
        <body>
        <h1>Diary</h1>
        EOM;
        $this->showTopBox();
        echo <<< EOM
        </body>
        </html>
        EOM;
    }

    private function showTopBox()
    {
        global $status;
        $scriptURL = $_SERVER['SCRIPT_NAME'];
        $sameStyle='';
        $editStyle='';
        $resultStyle='';
        $configStyle='';
        match($status->mode){
            'edit' => $editStyle='style="background-color: yellow;"',
            'result' => $resultStyle='style="background-color: aqua;"',
            'config' => $configStyle='style="background-color: bisque"',
            default => $sameStyle='style="background-color: lightgreen;"',
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

    public function changeMode()
    {
        global $status;
        if(isset($_POST['name'])){
            $status->mode = $_POST['name'];
            $this->showTopBox();
        }else{
            http_response_code(400);
            echo <<< EOM
            something wrong with the request
            EOM;
        }
    }

}
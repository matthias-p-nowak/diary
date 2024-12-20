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
        $newStyle='';
        $editStyle='';
        $resultStyle='';
        match($status->mode){
            'edit' => $editStyle='style="background-color: yellow;"',
            'result' => $resultStyle='style="background-color: aqua;"',
            default => $newStyle='style="background-color: lightgreen;"',
        };
        echo <<< EOM
        <div class="topbox">
        <span $newStyle>New</span>
        <span $editStyle>Edit</span>
        <span $resultStyle>Results</span>
        <span>Configure</span>
        </div>
        EOM;
    }

}
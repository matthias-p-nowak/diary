<?php
namespace Code;

class EventHandler
{
    /**
     * @return void
     */
    public static function Same_Event(): void
    {
        global $status;
        error_log(__FILE__ . ':' . __LINE__ . ' ' . __FUNCTION__ . print_r($_POST, true));
        if (isset($_POST['error'])) {
            error_log($_POST['error']);
        }
        $ip = $_SERVER['REMOTE_ADDR'];
        $ev = new EventHandler();
        $ev->sameEvent($ip);
    }
    /**
     * @return void
     * @param mixed $ip retrieved ip-address
     */
    private function sameEvent($ip): void
    {
        $id = $_POST['id'];
        $ev = new Db\Event();
        $db = Db\DbCtx::getCtx();
        if ($id > -1) {
            $row = $db->findRows('Event', ['id' => $id], ' limit 1')->current();
            $ev->Activity = $row->Activity;
        } else {
            $ev->Activity = '';
        }
        $ev->IP = $ip;
        if (isset($_POST['latitude'])) {
            $ev->Latitude = $_POST['latitude'];
        }
        if (isset($_POST['longitude'])) {
            $ev->Longitude = $_POST['longitude'];
        }
        $db->storeRow($ev);
        $ev=$db->findRows('Event',[],' order by Started desc limit 1');
        $ev=\iterator_to_array($ev)[0];
        $p=new Page();
        $p->editEvent($ev);
    }
    /**
     * @return void
     */
    public static function Edit_Event(): void
    {
        error_log(__FILE__.':'.__LINE__. ' '. __FUNCTION__.' editing event changed='.$_POST['name']);
        $db=Db\DbCtx::getCtx();
        $id=$_POST['id'];
        $name=$_POST["name"];
        if($name === 'delete'){
            $ev=new Db\Event();
            $ev->Id=$id;
            $db->deleteRow($ev);
            $lt='<!-- '.__FILE__.':'.__LINE__.' '.' -->';
            echo <<< EOM
            <div id="main" x-action="replace">
            $lt
            <h4>Row deleted</h4>
            </div
            EOM;
            return;
        }
        $row=$db->findRows('Event',['Id'=>$id])->current();
        $row->Activity=$_POST['activity'];
        $row->Details=$_POST['details'];
        $row->Started=$_POST['started'];
        $db->storeRow($row);
    }
}

<?php
namespace Code;

class ConfigHandler
{

    /**
     * @return void
     */
    public static function Edit_Activity(): void
    {
        $change = $_POST['name'];
        error_log(__FILE__ . ':' . __LINE__ . ' ' . __FUNCTION__ . ' ' . $change);
        $ch = new ConfigHandler();
        match ($change) {
            'activity' => $ch->changeActivity(),
            'delete' => $ch->deleteActivity(),
            'results' => $ch->changeResults(),
            'sel_parent' => $ch->changeParent(),
            default => $ch->notYetImplemented(),
        };
    }

    /**
     * @return void
     */
    private function notYetImplemented(): void
    {
        error_log(__FILE__ . ':' . __LINE__ . ' ' . __FUNCTION__ . ' ### not yet implemented');
    }

    /**
     * @return void
     */
    private function changeActivity(): void
    {
        error_log(__FILE__ . ':' . __LINE__ . ' ' . __FUNCTION__);
        $db = Db\DbCtx::getCtx();
        $sql = <<< 'EOS'
        UPDATE ${prefix}Activity set Parent=:new WHERE Parent=:old;
        UPDATE ${prefix}Activity set Activity=:new WHERE Activity=:old;
        UPDATE ${prefix}Event set Activity=:new WHERE Activity=:old;
        TRUNCATE TABLE ${prefix}Accounted;
        EOS;
        error_log($sql);
        $original = $_POST['original'];
        $newActivity = $_POST['activity'];
        $res = $db->query($sql, ['new' => $newActivity, 'old' => $original]);
        $p = new Page();
        $p->Show_Activity($newActivity);
    }
    /**
     * @return void
     */
    private function changeResults(): void
    {
        error_log(__FILE__.':'.__LINE__. ' '. __FUNCTION__);
        $db=Db\DbCtx::getCtx();
        $activity=$_POST['original'];
        $on= $_POST['results']=='on';
        $row=$db->findRows('Activity',['Activity' => $activity ])->current();
        error_log(print_r($row,true));
        $row->Results = $on ? 1 : 0;
        $db->storeRow($row);
    }
    /**
     * @return void
     */
    private function changeParent(): void
    {
        error_log(__FILE__.':'.__LINE__. ' '. __FUNCTION__);
        $db=Db\DbCtx::getCtx();
        $activity=$_POST['original'];
        $row=$db->findRows('Activity',['Activity' => $activity ])->current();
        if($_POST['sel_parent']== '-'){
            $row->Parent=null;
        }else{
            $row->Parent=$_POST['sel_parent'];
        }
        $db->storeRow($row);
    }
    /**
     * @return void
     */
    private function deleteActivity(): void
    {
        error_log(__FILE__.':'.__LINE__. ' '. __FUNCTION__);
        $db=Db\DbCtx::getCtx();
        $activity=$_POST['original'];
        $row=$db->findRows('Activity',['Activity' => $activity ])->current();
        $db->deleteRow($row);
        $p = new Page();
        $p->activities2Configure();
    }

}

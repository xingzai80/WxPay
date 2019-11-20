<?php
include './Base.php';

class H5pay extends Base
{
    public function __construct() {
        //;统一下单
        if(isset($_GET['oid'])){
            $oid = $_GET['oid'];
        } else {
            $oid = time();
        }
       $arr =  $this->unifiedOrder($oid,'h5');
      // echo '<PRE>';
       print_r($arr);die;
       header("location:" . $arr['mweb_url'] . '&redirect_url=' . urlencode(self::REDURL . '?oid=' .$oid ));
    }
}


$obj = new H5pay();
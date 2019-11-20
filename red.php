<?php
include './Base.php';



class SerachOrder extends Base
{
    public function __construct($oid) {
        //;
       $arr = $this->sOrder($oid);
       echo '<pre>';
       print_r($arr);
    }
}



if(isset($_GET['oid'])  && isset($_GET['search'])){
    $oid = $_GET['oid'];
    $obj = new SerachOrder($oid);
}
?>

<a href="red.php?oid=<?php echo $_GET['oid'];?>&search=1">支付成功</a>  <a href="h5.php?oid=<?php echo $_GET['oid'];?>">支付遇到问题</a>
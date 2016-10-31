<?php
class jiemi{
    private $key='Acxylf365jw';
    //private $key='Encrypt01';

    public function ReverseStr($str){
        $str=implode(array_reverse(str_split($str,1)));
        return $str;
    }

    public function Decode($PlainStr){
        $key=$this->key;
        $Pos=1;
        if(strlen($PlainStr)%2 == 0){
            $Side1 = $this->ReverseStr(substr($PlainStr,0,strlen($PlainStr)/2));
            $Side2 = $this->ReverseStr(substr($PlainStr,strlen($PlainStr)/2));
            $PlainStr = $Side1.$Side2;
        }

        $size = strlen($PlainStr);
        $NewStr = '';
        for($i=1;$i<=$size;$i++){
            $strChar = substr($PlainStr,$i-1,1);
            $KeyChar = substr($key,$Pos-1,1);

            $bl_1 = (ord($strChar) ^ ord($KeyChar)) < 32? 1:0;
            $bl_2 = (ord($strChar) ^ ord($KeyChar)) > 126? 1:0;
            $bl_3 = (ord($strChar) < 0? 1:0) | ($bl_1 | $bl_2);
            $bl_4 = (ord($strChar) > 0xFF? 1:0) | $bl_3;
            if($bl_4){
                $NewStr .= $strChar;
            }else{
                $ch = ord($strChar) ^ ord($KeyChar);
                $str = "";
                $str .= chr($ch);
                $NewStr .= $str;
            }
            if(strlen($key) == $Pos){
                $Pos = 0;
            }
            $Pos += 1;
        }
        $jiemi = $NewStr;
        return $jiemi;
    }
}
?>

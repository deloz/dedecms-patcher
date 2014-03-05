<?php
/**
 * ϵͳ����
 *
 * @version        $Id: sys_info.php 1 22:28 2010��7��20��Z tianya $
 * @package        DedeCMS.Administrator
 * @copyright      Copyright (c) 2007 - 2010, DesDev, Inc.
 * @license        http://help.dedecms.com/usersguide/license.html
 * @link           http://www.dedecms.com
 */
require_once(dirname(__FILE__)."/config.php");
CheckPurview('sys_Edit');
if(empty($dopost)) $dopost = "";

$configfile = DEDEDATA.'/config.cache.inc.php';

//�������ú���
function ReWriteConfig()
{
    global $dsql,$configfile;
    if(!is_writeable($configfile))
    {
        echo "�����ļ�'{$configfile}'��֧��д�룬�޷��޸�ϵͳ���ò�����";
        exit();
    }
    $fp = fopen($configfile,'w');
    flock($fp,3);
    fwrite($fp,"<"."?php\r\n");
    $dsql->SetQuery("SELECT `varname`,`type`,`value`,`groupid` FROM `#@__sysconfig` ORDER BY aid ASC ");
    $dsql->Execute();
    while($row = $dsql->GetArray())
    {
        if($row['type']=='number')
        {
            if($row['value']=='') $row['value'] = 0;
            fwrite($fp,"\${$row['varname']} = ".$row['value'].";\r\n");
        }
        else
        {
            fwrite($fp,"\${$row['varname']} = '".str_replace("'",'',$row['value'])."';\r\n");
        }
    }
    fwrite($fp,"?".">");
    fclose($fp);
}

//�������õĸĶ�
if($dopost=="save")
{
    foreach($_POST as $k=>$v)
    {
        if(preg_match("#^edit___#", $k))
        {
            $v = cn_substrR(${$k}, 1024);
        }
        else
        {
            continue;
        }
        $k = preg_replace("#^edit___#", "", $k);
        $dsql->ExecuteNoneQuery("UPDATE `#@__sysconfig` SET `value`='$v' WHERE varname='$k' ");
    }
    ReWriteConfig();
    ShowMsg("�ɹ�����վ�����ã�", "sys_info.php");
    exit();
}
//�����±���
else if($dopost=='add')
{
    if($vartype=='bool' && ($nvarvalue!='Y' && $nvarvalue!='N'))
    {
        ShowMsg("��������ֵ����Ϊ'Y'��'N'!","-1");
        exit();
    }
    if(trim($nvarname)=='' || preg_match("#[^a-z_]#i", $nvarname) )
    {
        ShowMsg("����������Ϊ�ղ��ұ���Ϊ[a-z_]���!","-1");
        exit();
    }
    $row = $dsql->GetOne("SELECT varname FROM `#@__sysconfig` WHERE varname LIKE '$nvarname' ");
    if(is_array($row))
    {
        ShowMsg("�ñ��������Ѿ�����!","-1");
        exit();
    }
    $row = $dsql->GetOne("SELECT aid FROM `#@__sysconfig` ORDER BY aid DESC ");
    $aid = $row['aid'] + 1;
    $inquery = "INSERT INTO `#@__sysconfig`(`aid`,`varname`,`info`,`value`,`type`,`groupid`)
    VALUES ('$aid','$nvarname','$varmsg','$nvarvalue','$vartype','$vargroup')";
    $rs = $dsql->ExecuteNoneQuery($inquery);
    if(!$rs)
    {
        ShowMsg("��������ʧ�ܣ������зǷ��ַ���", "sys_info.php?gp=$vargroup");
        exit();
    }
    if(!is_writeable($configfile))
    {
        ShowMsg("�ɹ���������������� $configfile �޷�д�룬��˲��ܸ��������ļ���","sys_info.php?gp=$vargroup");
        exit();
    }else
    {
        ReWriteConfig();
        ShowMsg("�ɹ�������������������ļ���","sys_info.php?gp=$vargroup");
        exit();
    }
}
// ��������
else if ($dopost=='search')
{
    $keywords = isset($keywords)? strip_tags($keywords) : '';
    $i = 1;
    $configstr = <<<EOT
 <table width="100%" cellspacing="1" cellpadding="1" border="0" bgcolor="#cfcfcf" id="tdSearch" style="">
  <tbody>
   <tr height="25" bgcolor="#fbfce2" align="center">
    <td width="300">����˵��</td>
    <td>����ֵ</td>
    <td width="220">������</td>
   </tr>
EOT;
    echo $configstr;
    if ($keywords)
    {

        $dsql->SetQuery("SELECT * FROM `#@__sysconfig` WHERE info LIKE '%$keywords%' order by aid asc");
        $dsql->Execute();
       
        while ($row = $dsql->GetArray()) {
            $bgcolor = ($i++%2==0)? "#F9FCEF" : "#ffffff";
            $row['info'] = preg_replace("#{$keywords}#", '<font color="red">'.$keywords.'</font>', $row['info']);
?>
      <tr align="center" height="25" bgcolor="<?php echo $bgcolor?>">
       <td width="300"><?php echo $row['info']; ?>�� </td>
       <td align="left" style="padding:3px;">
<?php
    if($row['type']=='bool')
    {
        $c1='';
        $c2 = '';
        $row['value']=='Y' ? $c1=" checked" : $c2=" checked";
        echo "<input type='radio' class='np' name='edit___{$row['varname']}' value='Y'$c1>�� ";
        echo "<input type='radio' class='np' name='edit___{$row['varname']}' value='N'$c2>�� ";
    }else if($row['type']=='bstring')
    {
        echo "<textarea name='edit___{$row['varname']}' row='4' id='edit___{$row['varname']}' class='textarea_info' style='width:98%;height:50px'>".htmlspecialchars($row['value'])."</textarea>";
    }else if($row['type']=='number')
    {
        echo "<input type='text' name='edit___{$row['varname']}' id='edit___{$row['varname']}' value='{$row['value']}' style='width:30%'>";
    }else
    {
        echo "<input type='text' name='edit___{$row['varname']}' id='edit___{$row['varname']}' value=\"".htmlspecialchars($row['value'])."\" style='width:80%'>";
    }
    ?>
</td>
       <td><?php echo $row['varname']?></td>
      </tr>
      <?php
}
?>
     </table>
      <?php
        exit;
    }
    if ($i == 1)
    {
        echo '      <tr align="center" bgcolor="#F9FCEF" height="25">
           <td colspan="3">û���ҵ�����������</td>
          </tr></table>';
    }
    exit;
} else if ($dopost=='make_encode')
{
    $chars='abcdefghigklmnopqrstuvwxwyABCDEFGHIGKLMNOPQRSTUVWXWY0123456789';
    $hash='';
    $length = rand(28,32);
    $max = strlen($chars) - 1;
    for($i = 0; $i < $length; $i++) {
        $hash .= $chars[mt_rand(0, $max)];
    }
    echo $hash;
    exit();
}

include DedeInclude('templets/sys_info.htm');
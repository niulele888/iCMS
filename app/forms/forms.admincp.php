<?php
/**
* iCMS - i Content Management System
* Copyright (c) 2007-2017 iCMSdev.com. All rights reserved.
*
* @author icmsdev <master@icmsdev.com>
* @site https://www.icmsdev.com
* @licence https://www.icmsdev.com/LICENSE.html
*/
defined('iPHP') OR exit('What are you doing?');

class formsAdmincp{
    public function __construct() {
      $this->appid = iCMS_APP_FORMS;
      $this->id = (int)$_GET['id'];
      $_GET['form_id'] && $this->form_id = (int)$_GET['form_id'];
    }
    public function form_init(){
      $this->form = forms::get($this->form_id);
    }
    /**
     * [添加表单内容]
     * @return [type] [description]
     */
    public function do_submit(){
      empty($this->form_id) && admincp::error_page("请选择要添加的表单");
      $this->form_init();
      $rs = forms::get_data($this->form,$this->id);
      iPHP::callback(array("formerApp","add"),array($this->form,$rs));
      include admincp::view('forms.submit');
    }
    /**
     * [保存表单数据]
     * @return [type] [description]
     */
    public function do_savedata($dialog=true){
      $this->form_id = (int)$_POST['form_id'];
      $this->form_init();
      $update = iPHP::callback(array("formerApp","save"),array($this->form));
      $REFERER_URL = $_POST['REFERER'];
      if(empty($REFERER_URL)||strstr($REFERER_URL, '=form_save')){
          $REFERER_URL= APP_URI.'&do=form_manage&form_id='.$this->form_id;
      }
      if($dialog){
        if($update){
            iUI::success($this->form['name'].'编辑完成!<br />3秒后返回'.$this->form['name'].'列表','url:'.$REFERER_URL);
        }else{
            iUI::success($this->form['name'].'添加完成!<br />3秒后返回'.$this->form['name'].'列表','url:'.$REFERER_URL);
        }
      }else{
        return $update;
      }
    }
    /**
     * [表单数据查看]
     * @param  string $stype [description]
     * @return [type]        [description]
     */
    public function do_data($stype='normal') {
        empty($this->form_id) && admincp::error_page("请选择要查看的表单数据");
        $this->form_init();
        $table_array = apps::get_table($this->form);
        $table       = $table_array['table'];
        $primary     = $table_array['primary'];

        $sql = "WHERE 1=1";

        if($_GET['keywords']) {
          $sql.=" AND title REGEXP '{$_GET['keywords']}'";
        }

        isset($_GET['keywords'])&& $uri.='&keyword='.$_GET['keywords'];

        $orderby    = $_GET['orderby']?$_GET['orderby']:"{$primary} DESC";
        $maxperpage = $_GET['perpage']>0?(int)$_GET['perpage']:20;
        $total      = iCMS::page_total_cache("SELECT count(*) FROM `{$table}` {$sql}","G");
        iUI::pagenav($total,$maxperpage,"条记录");

        $rs = iDB::all("SELECT * FROM `{$table}` {$sql} order by {$orderby} LIMIT ".iUI::$offset." , {$maxperpage}");
        $_count = count($rs);
        if($this->form['fields']){
            $fields = former::fields($this->form['fields']);
        }
        include admincp::view('forms.data');
    }
    /**
     * [删除表单数据]
     * @param  [type]  $id     [description]
     * @param  boolean $dialog [description]
     * @return [type]          [description]
     */
    public function do_delete($id = null,$dialog=true){
      $id===null && $id=$this->id;
      $id OR iUI::alert("请选择要删除的{$this->form['name']}数据");
      $this->form_id = (int)$_POST['form_id'];
      $this->form_init();

      $tables = $this->form['table'];
      if($tables)foreach ($tables as $key => $value) {
          $primary_key = $value['primary'];
          $value['union'] && $primary_key = $value['union'];
          iDB::query("DELETE FROM `{$value['table']}` WHERE `{$primary_key}`='$id'");
      }
      $dialog && iUI::success("{$this->form['name']}数据删除完成",'js:parent.$("#tr'.$id.'").remove();');
    }

    /**
     * [创建表单]
     * @return [type] [description]
     */
    public function do_create(){
        $this->id && $rs = forms::get($this->id);
        if(empty($rs)){
          $rs['type']   = "1";
          $rs['status'] = "1";
          $rs['fields'] = forms::base_fields_json();
          $rs['fields'] = json_decode($rs['fields'],true);
          $base_fields  = forms::base_fields_array();
        }
        empty($rs['tpl']) && $rs['tpl'] = '{iTPL}/forms.htm';
        $rs['app'] = ltrim($rs['app'],'forms_');
        include admincp::view("forms.create");
    }
  /**
   * [保存表单]
   * @return [type] [description]
   */
    public function do_save(){
        $id    = (int)$_POST['_id'];
        $app   = iSecurity::escapeStr($_POST['_app']);
        $name  = iSecurity::escapeStr($_POST['_name']);
        $title = iSecurity::escapeStr($_POST['_title']);
        $tpl   = iSecurity::escapeStr($_POST['_tpl']);
        $pic   = iSecurity::escapeStr($_POST['_pic']);
        $description   = iSecurity::escapeStr($_POST['_description']);
        $type    = (int)$_POST['type'];
        $status  = (int)$_POST['status'];

        $name OR iUI::alert('表单名称不能为空!');
        empty($app) && $app = iPinyin::get($name);
        empty($title) && $title = $name;
        $app = 'forms_'.ltrim($app,'forms_');

        $table_array  = $_POST['table'];
        if($table_array){
          $table_array  = array_filter($table_array);
          $table  = addslashes(cnjson_decode($table_array));
        }

        $config_array = $_POST['config'];
        if($config_array){
          $config_array = array_filter($config_array);
          $config       = addslashes(cnjson_decode($config_array));
        }

        $fields   = '';
        $fieldata = $_POST['fields'];
        if(is_array($fieldata)){
          $field_array = array();
          foreach ($fieldata as $key => $value) {
            $output = array();
            parse_str($value,$output);
            if(isset($output['UI:BR'])){
              $field_array[$key] = 'UI:BR';
            }else{
              $output['label'] OR iUI::alert('发现自定义字段中空字段名称!');
              $output['comment'] = $output['label'].($output['comment']?':'.$output['comment']:'');
              $fname = $output['name'];
              $fname OR iUI::alert('发现自定义字段中有空字段名!');
              $field_array[$fname] = $value;
              if($output['field']=="MEDIUMTEXT"){
                $addons_fieldata[$key] = $value;
                unset($fieldata[$key]);//从基本表移除
              }
            }
          }
          //字段数据存入数据库
          $fields = addslashes(cnjson_decode($field_array));
        }

        iFS::$force_ext = "jpg";
        iFS::checkHttp($pic) && $pic = iFS::http($pic);

        $addtime = time();
        $array   = compact(array('app','name','title','pic','description','tpl','table','config','fields','addtime','type','status'));

        if(empty($id)) {

            iDB::value("SELECT `id` FROM `#iCMS@__forms` where `app` ='$app'") && iUI::alert('该表单已经存在!');
            apps_db::check_table(iDB::table($array['app'])) && iUI::alert('['.$array['app'].']数据表已经存在!');

            // iDB::$print_sql = true;

            if($addons_fieldata){
              $addons_name = apps_mod::data_table_name($array['app']);
              apps_db::check_table(iDB::table($addons_name)) && iUI::alert('['.$addons_name.']附加表已经存在!');
            }

            //创建基本表
            $tb = apps_db::create_table(
              $array['app'],
              apps_mod::get_field_array($fieldata),//获取字段数组
              forms::base_fields_index()//索引
            );
            array_push ($tb,null,$array['name']);
            $table_array = array();
            $table_array[$array['app']]= $tb;//记录基本表名

            //有MEDIUMTEXT类型字段就创建xxx_data附加表
            if($addons_fieldata){
              $union_id = apps_mod::data_union_id($array['app']);//关联基本表id
              $addons_base_fields = apps_mod::base_fields($array['app']);//xxx_data附加表的基础字段
              $addons_fieldata = $addons_base_fields+$addons_fieldata;
              $table_array += apps_mod::data_create_table($addons_fieldata,$addons_name,$union_id);
              // //添加到字段数据里
              // $field_array = array_merge($field_array,$addons_base_fields);
              // $array['fields'] = addslashes(cnjson_decode($field_array));
            }
            $array['table']  = $table_array;
            $array['config'] = $config_array;

            $array['table'] = addslashes(cnjson_decode($table_array));
            $array['config'] = addslashes(cnjson_decode($config_array));

            $id = iDB::insert('forms',$array);

            $msg = "表单创建完成!";
        }else {
            iDB::value("SELECT `id` FROM `#iCMS@__forms` where `app` ='$app' AND `id` !='$id'") && iUI::alert('该表单已经存在!');
            $_fields     = iDB::value("SELECT `fields` FROM `#iCMS@__forms` where `id` ='$id'");//json
            $_json_field = apps_mod::json_field($_fields);//旧数据
            $json_field  = apps_mod::json_field($fields); //新数据
            /**
             * 找出字段数据中的 MEDIUMTEXT类型字段
             * PS:函数内会unset(json_field[key]) 所以要在 基本表make_alter_sql前执行
             */
            $_addons_json_field = apps_mod::find_MEDIUMTEXT($_json_field);
            $addons_json_field = apps_mod::find_MEDIUMTEXT($json_field);

            // print_r($_addons_json_field);
            // print_r($addons_json_field);

            //基本表 新旧数据计算交差集 origin 为旧字段名
            $sql_array = apps_db::make_alter_sql($json_field,$_json_field,$_POST['origin']);
            $sql_array && apps_db::alter_table($array['app'],$sql_array);

            //MEDIUMTEXT类型字段 新旧数据计算交差集 origin 为旧字段名
            $addons_sql_array = apps_db::make_alter_sql($addons_json_field,$_addons_json_field,$_POST['origin']);

            $addons_name = apps_mod::data_table_name($array['app']);
            //存在附加表数据
            if($addons_fieldata){
              if($addons_sql_array){
                //附加表名
                //检测附加表是否存在
                if($table_array[$addons_name] && apps_db::check_table(iDB::table($addons_name))){
                  //表存在执行 alter
                  apps_db::alter_table($addons_name,$addons_sql_array);
                }else{
                  // 不存在 创建
                  if($addons_fieldata){
                    apps_db::check_table(iDB::table($addons_name)) && iUI::alert('['.$addons_name.']附加表已经存在!');
                    //有MEDIUMTEXT类型字段创建xxx_data附加表
                    $union_id = apps_mod::data_union_id($array['app']);
                    $addons_base_fields = apps_mod::base_fields($array['app']);//xxx_data附加表的基础字段
                    $addons_fieldata = $addons_base_fields+$addons_fieldata;
                    $table_array += apps_mod::data_create_table($addons_fieldata,$addons_name,$union_id);
                    $array['table'] = addslashes(cnjson_decode($table_array));
                    // //添加到字段数据里
                    // $field_array = array_merge($field_array,$addons_base_fields);
                    // $array['fields'] = addslashes(cnjson_decode($field_array));
                  }
                }
              }
            }else{
                //删除自定义表单的表
                //不存在附加表数据 直接删除附加表 返回 table的json值 $table_array为引用参数
                apps_mod::drop_table($addons_fieldata,$table_array,$addons_name);
                $array['table'] = addslashes(cnjson_decode($table_array));
            }

            iDB::update('forms', $array, array('id'=>$id));
            $msg = "表单编辑完成!";
        }
        apps::cache();
        iUI::success($msg,'url:'.APP_URI);
    }

    public function do_update(){
        if($this->id){
            $args = admincp::update_args($_GET['_args']);
            $args && iDB::update("forms",$args,array('id'=>$this->id));
            apps::cache();
            iUI::success('操作成功!','js:1');
        }
    }
    public function do_iCMS(){
      // if($_GET['keywords']) {
		    // $sql=" WHERE `keyword` REGEXP '{$_GET['keywords']}'";
      // }
      $orderby    =$_GET['orderby']?$_GET['orderby']:"id DESC";
      $maxperpage = $_GET['perpage']>0?(int)$_GET['perpage']:50;
      $total      = iCMS::page_total_cache("SELECT count(*) FROM `#iCMS@__forms` {$sql}","G");
      iUI::pagenav($total,$maxperpage,"个表单");
      $rs     = iDB::all("SELECT * FROM `#iCMS@__forms` {$sql} order by {$orderby} LIMIT ".iUI::$offset." , {$maxperpage}");
    	include admincp::view("forms.manage");
    }

    public function do_batch(){
        $idArray = (array)$_POST['id'];
        $idArray OR iUI::alert("请选择要操作的表单");
        $ids     = implode(',',$idArray);
        $batch   = $_POST['batch'];
        switch($batch){
          case 'data-dels':
            iUI::$break = false;
            foreach($idArray AS $id){
              $this->do_delete($id,false);
            }
            iUI::$break = true;
            iUI::success('全部删除完成!','js:1');
          break;
          case 'dels':
            iUI::$break = false;
            foreach($idArray AS $id){
              $this->do_del($id,false);
            }
            iUI::$break = true;
            iUI::success('全部删除完成!','js:1');
          break;
        }

	  }

    /**
     * [删除表单]
     * @return [type] [description]
     */
    public function do_del($id = null,$dialog=true){
      $id===null && $id=$this->id;
      $id OR iUI::alert('请选择要删除的表单!');
      $forms = forms::get($id);
      forms::delete($this->id);
      $dialog && iUI::success("表单已经删除!",'url:'.APP_URI);
    }
    /**
     * [本地安装表单]
     * @return [type] [description]
     */
    public function do_local_forms(){
      if(strpos($_POST['zipfile'], '..') !== false){
        iUI::alert('What the fuck!!');
      }
      forms_zip::$zipFile  = trim($_POST['zipfile'],"\0\n\r\t\x0B");
      forms_zip::$msg_mode = 'alert';
      forms_zip::install();
      iUI::success('表单安装完成','js:1');
    }
    /**
     * [打包下载表单]
     * @return [type] [description]
     */
    public function do_pack(){
      $rs = iDB::row("SELECT * FROM `#iCMS@__forms` where `id`='".$this->id."'",ARRAY_A);
      unset($rs['id']);
      $data     = base64_encode(serialize($rs));
      $filename = 'iCMS.FORMS.'.$rs['app'];
      //自定义表单
      $appdir = iPHP_APP_CACHE.'/pack.forms/'.$rs['app'];
      $remove_path = iPHP_APP_CACHE.'/pack.forms/';
      iFS::mkdir($appdir);

      //表单数据
      $app_data_file = $appdir.'/iCMS.APP.DATA.php';
      put_php_file($app_data_file, $data);

      //数据库结构
      if($rs['table']){
        $app_table_file = $appdir.'/iCMS.APP.TABLE.php';

        put_php_file(
          $app_table_file,
          apps_db::create_table_sql($rs['table'])
        );
      }

      $zipfile = apps::get_zip($filename,$appdir,$remove_path);
      filesApp::attachment($zipfile);
      iFS::rm($zipfile);
      iFS::rm($app_data_file);
      $app_table_file && iFS::rm($app_table_file);
      iFS::rmdir($remove_path);
    }
    public function setup_zipurl($url,$name,$zipname=null){
          // apps_store::$test = true;
        $msg = apps_store::download($url,$name,$zipname);
        $msg.= apps_store::install();
        $msg = str_replace('<iCMS>', '<br />', $msg);
        if(apps_store::$app_id){
          iUI::dialog($msg,'url:'.APP_URI."&do=add&id=".apps_store::$app_id,10);
        }else{
          iUI::dialog($msg,'js:1',3);
        }
    }
}

<?php
/**
 * 默认的 controller
 *
 * @author Medz Seven <lovevipdsw@vip.qq.com>
 * @copyright ©2012-2015 medz.cn
 * @license http://www.medz.cn
 * @version $Id$
 */
class Index extends WindController {

	protected $notFileDir = array();

	/* (non-PHPdoc)
	 * @see WindSimpleController::beforeAction()
	 */
	public function beforeAction($handlerAdapter) {
		parent::beforeAction($handlerAdapter);
		//$this->setLayout('layout');
		$this->setOutput('utf8', 'charset');
		//Wind::getApp()->setGlobal($this->getRequest()->getBaseUrl(true) . '/source/images', 'images');
		//Wind::getApp()->setGlobal($this->getRequest()->getBaseUrl(true) . '/source/css', 'css');
		//if(!$this->getInput('start')) {}
		$this->notFileDir = array(
			PW_ROOT . SLASH . 'attachment',
			PW_ROOT . SLASH . 'windid' . SLASH . 'attachment',
			ROOT . 'back' . SLASH . 'data' . SLASH . 'medz',
			ROOT . 'back' . SLASH . 'data' . SLASH . 'table.sql',
			ROOT . 'back' . SLASH . 'data' . SLASH . 'auto.sql',
		);
		@set_time_limit(0);
	}

	/* (non-PHPdoc)
	 * @see WindController::run()
	 */
	public function run() {
		// #检测pw的配置文件是否存在
		$confFile = PW_ROOT . 'conf' . SLASH . 'database.php';
		if(!file_exists($confFile)) {
			$this->show('原pw9程序数据库配置文件不存在！');
		}

		// #尝试读取该文件~检测是否有权限读取
		if(!file_get_contents($confFile)) {
			$this->show('文件没有读取权限~');
		}

		// #检测配置文件内容是否符合正常需求
		$conf = include $confFile;
		if(!is_array($conf)) {
			$this->show('原程序数据库配置不存在，或者没有读取权限~读取失败！');
		}

		if($conf['charset'] == 'utf8') {
			$this->show('你的原程序就是UTF-8编码~无需转换~');
		}

		// #将原数据库信息保存到转成程序中
		//file_put_contents(ROOT . 'dataBase.php', $data);
		$conf['charset'] = 'utf8';

		// #检测转换程序所需要的目录是否是可操作的~
		$data = 'medz';
		$dirArray = array(
			ROOT,
			PW_ROOT,
			ROOT . 'back',
			ROOT . 'back' . SLASH . 'code',
			ROOT . 'back' . SLASH . 'data',
		);

		$dirArray = array_merge($dirArray, $this->getDir(PW_ROOT));

		foreach($dirArray as $dir) {
			if(!is_writable($dir)) {
				$this->show($dir . ' 目录不可写！');
			} else {
				file_put_contents($dir . SLASH . 'medz.test', $data);
			}
			if(!file_get_contents($dir . SLASH . 'medz.test')) {
				$this->show($dir . ' 目录不可读！');
			}
			if(!is_readable($dir . SLASH . 'medz.test')) {
				$this->show($dir . ' 目录不可读！');
			}
			unlink($dir . SLASH . 'medz.test');
			if(file_exists($dir . SLASH . 'medz.test')) {
				$this->show($dir . ' 目录转换程序没有删除权限~！');
			}
		}
		unset($dirArray);

		foreach($this->getFile(PW_ROOT) as $file) {
			if(!is_readable($file)) {
				$this->show($file . '文件不可读~');
			}
		}

		WindFile::savePhpData(ROOT . 'conf' . SLASH . 'dataBase.php', $conf);
		WindFile::savePhpData(ROOT . 'back' . SLASH . 'num.php', '0');
		WindFile::savePhpData(ROOT . 'back' . SLASH . 'table.key.php', array());
		$html  = '环境检测通过~';
		$html .= '<a href="' . $this->URL('db') . '" ';
		$html .= '>点击这里</a>';
		$html .= '开始转换站点，站点转换为全自动完成~转换期间，确保浏览器不会被关闭~以及确保不要执行任何刷新操作~否则站点数据丢失自己负责。<br>';
		$html .= '在转换站点前，请先手动备份好数据库以及站点程序代码~因为转换期间~将会对整个站点的所有文件经常删除和重写~<br>';
		$html .= '以及数据库的删除和重建表以及插入数据操作~';
		$html .= '本站安装的插件和模板~不会自动转换~所以请把您所安装的插件和模板文件手动下载到本地~<br>';
		$html .= '然后把插件和模板压缩成一个zip压缩包~执行附带转换程序的工具~';
		$html .= '工具文件名：MedzToolConverts.php<br>';
		$html .= '工具运行地址：http://你的pw程序地址/convert/MedzToolConverts.php';
		$this->show($html);
	}

	public function dbAction() {
		switch(intval($this->getInput('step'))) {
			case 3:
				$limit = 100;
				$id    = $this->getInput('id');
				$id or $id = 1;
				$id    = intval($id);
				$table = $this->getInput('table');
				$table or $this->jump('传参错误~程序自动返回重新执行。', 'db', array('step' => 2));
				$count = $this->db()->createStatement("SELECT COUNT(*) as num FROM `{$table}`")->getValue();
				if($limit > $count or ($id * $limit) >= $count) {
					$dataNum = $count;
				} else {
					$dataNum = $id * $limit;
				}

				$offset = max(($id - 1) * $limit, 0);
				$limit = ' LIMIT ' . max(0, intval($offset)) . ',' . max(1, intval($limit));

				$sql  = '';
				$sql .= "SELECT * FROM `{$table}`" . $limit;

				$data = $this->db()->createStatement($sql)->queryAll();
				if(!$data) {
					$this->jump($table . '表处理完成!', 'db', array('step' => 2));
				}

				$sql = '';
				foreach($data as $_val) {
					$sql .= 'INSERT INTO `' . $table . '` (';
					$_sqlKey = array();
					$_sqlValue = array();
					foreach ($_val as $key => $value) {
						$_sqlKey[] = '`' . $key . '`';
						$_sqlValue[] = "'" . $value . "'";
					}
					$_sqlKey = implode(',', $_sqlKey);
					$_sqlValue = implode(',', $_sqlValue);
					$sql .= $_sqlKey . ') VALUES(' . $_sqlValue . ');';
					$sql .= "\n\r\n";
				}

				$num = include ROOT . 'back' . SLASH . 'num.php';
				$num or $num = 0;
				$num += 1;

				WindFile::savePhpData(ROOT . 'back' . SLASH . 'num.php', $num);
				WindFile::write(ROOT . 'back' . SLASH . 'data' . SLASH . $num . '.sql', $sql);
				$tableKey = include ROOT . 'back' . SLASH . 'table.key.php';
				$tableKey[] = $num;
				WindFile::savePhpData(ROOT . 'back' . SLASH . 'table.key.php', $tableKey);

				$id += 1;
				$this->jump($table . '表已处理' . $dataNum . '条数据，总共' . $count . '条！', 'db', array('step' => 3, 'id' => $id, 'table' => $table));
				break;
			case 2:
				$tables = include ROOT . 'back' . SLASH . 'table.index.php';
				if(!$tables) {
					$this->jump('数据库处理完毕！', 'inSql', array('step' => 1));
				}
				$table = $tables['0'];
				unset($tables['0']);
				rsort($tables);
				WindFile::savePhpData(ROOT . 'back' . SLASH . 'table.index.php', $tables);
				$this->jump('正在处理数据。', 'db', array('step' => 3, 'table' => $table));
				break;
			case 1:
				$tables = $this->db()->query('SHOW TABLES')->fetchAll(); 
				$prefix = $this->db()->getTablePrefix();
				$prefixLen = strlen($prefix);
				$sql = '';
				$auto = '';
				$tableArray = array();
				foreach ($tables as $v) {
					$name = array_values($v);
					if (!$name[0]) continue;
					if (substr($name[0], 0, $prefixLen) != $prefix) continue;
					$table = '';
					$table = $this->db()->createStatement("SHOW CREATE TABLE `{$name[0]}`")->getOne();
					$table = $table['Create Table'];
					$sql  .= "DROP TABLE IF EXISTS `{$name[0]}`;\n";
					$autoIncrement = $this->db()->createStatement("SHOW TABLE STATUS LIKE '{$name[0]}'")->getOne();
					$sql  .= $this->SQL($table, $autoIncrement['Auto_increment']);
					$tableArray[] = $name[0];
					if($autoIncrement['Auto_increment']) {
						$auto .= "ALTER TABLE {$name[0]} AUTO_INCREMENT={$autoIncrement['Auto_increment']}; \n\r\n";
					}
				}
				WindFile::write(ROOT . 'back' . SLASH . 'data' . SLASH . 'table.sql', $sql);
				WindFile::write(ROOT . 'back' . SLASH . 'data' . SLASH . 'auto.sql', $auto);
				WindFile::savePhpData(ROOT . 'back' . SLASH . 'table.index.php', $tableArray);
				unset($tableArray);
				unset($sql);
				$this->jump('表结构备份完毕~', 'db', array('step' => 2));
				break;
			default:
				file_exists(PW_ROOT . 'index.php') and rename(PW_ROOT . 'index.php', PW_ROOT . 'index.php.medz');
				file_exists(PW_ROOT . 'admin.php') and rename(PW_ROOT . 'admin.php', PW_ROOT . 'admin.php.medz');
				file_exists(PW_ROOT . 'read.php') and rename(PW_ROOT . 'read.php', PW_ROOT . 'read.php.medz');
				file_exists(PW_ROOT . 'notify.php') and rename(PW_ROOT . 'notify.php', PW_ROOT . 'notify.php.medz');
				file_exists(PW_ROOT . 'install.php') and rename(PW_ROOT . 'install.php', PW_ROOT . 'install.php.medz');
				file_exists(PW_ROOT . 'thumb.php') and rename(PW_ROOT . 'thumb.php', PW_ROOT . 'thumb.php.medz');
				file_exists(PW_ROOT . 'windid.php') and rename(PW_ROOT . 'windid.php', PW_ROOT . 'windid.php.medz');
				file_exists(PW_ROOT . 'windid' . SLASH . 'admin.php') and rename(PW_ROOT . 'windid' . SLASH . 'admin.php', PW_ROOT . 'windid' . SLASH . 'admin.php.medz');
				$this->jump('必要安全处理完成~', 'db', array('step' => 1));
				break;
		}
	}

	public function inSqlAction() {
		switch (intval($this->getInput('step'))) {
			case 2:
				/*$sql = WindFile::read(ROOT . 'data' . SLASH . 'pw.sql');
				$this->db()->execute($sql);
				$sql = '';
				$sql = WindFile::read(ROOT . 'back' . SLASH . 'data' . SLASH . 'auto.sql');
				$this->db()->execute($sql);
				$conf = include ROOT . 'conf' . SLASH . 'dataBase.php';
				$conf['tableprefix'] = 'pw_';
				WindFile::savePhpData(ROOT . 'conf' . SLASH . 'dataBase.php', $conf);*/
				$this->jump('数据库表转换完成，接下来转换数据。', 'inSql');
				break;
			case 1:
				$sql = WindFile::read(ROOT . 'back' . SLASH . 'data' . SLASH . 'table.sql');
				$this->db()->execute($sql);
				$this->jump('数据库表清理完成，接下来转换数据表。', 'inSql', array('step' => 2));
				break;
			default:
				$tableKey = include ROOT . 'back' . SLASH . 'table.key.php';
				if(!$tableKey) {
					$this->jump('数据转换完成~', 'file');
				}
				$key = $tableKey['0'];
				unset($tableKey['0']);
				rsort($tableKey);
				WindFile::savePhpData(ROOT . 'back' . SLASH . 'table.key.php', $tableKey);
				$sqlFile = ROOT . 'back' . SLASH . 'data' . SLASH . $key . '.sql';
				$sql = WindFile::read($sqlFile);
				$this->db()->execute($sql);
				$this->jump('还有' . count($tableKey) . '份数据没有转换~请耐心等待~', 'inSql');
				break;
		}
	}

	public function fileAction() {
		switch(intval($this->getInput('step'))) {
			case 4:
				$url = $this->newURL(1);
				$url = preg_replace("/index.php?/is", 'medz.php?', $url);
				$url .= '&back=' . urlencode($this->URL('back'));
				$this->show('安全处理完成~正在进行文件的恢复操作~', $url);
				break;
			case 3:
				$files = $this->getFile(PW_ROOT, array(), array(PW_ROOT . SLASH . 'convert'));
				$dirs = $this->getDir(PW_ROOT, array(), array(PW_ROOT . SLASH . 'convert'));
				WindFile::savePhpData(ROOT . 'conf' . SLASH . 'files.php', $files);
				WindFile::savePhpData(ROOT . 'conf' . SLASH . 'dirs.php', $dirs);
				$this->jump('原程序备份完成~下一步执行新编码程序的还原~', 'file', array('step' => 4));
				break;
			case 2:
				$files = $this->getFile(PW_ROOT, array(), array(PW_ROOT . SLASH . 'convert'));
				foreach ($files as $file) {
					$data = WindFile::read($file);
					$file = explode(PW_ROOT, $file);
					$file = $file['1'];
					WindFile::write(ROOT . 'back' . SLASH . 'code' . $file, $data);
				}
				$this->jump('原程序文件备份完成~', 'file', array('step' => 3));
			case 1:
			default:
				$dirs = $this->getDir(PW_ROOT, array(), array(PW_ROOT . SLASH . 'convert'));
				foreach ($dirs as $dir) {
					$dir = explode(PW_ROOT, $dir);
					$dir = $dir['1'];
					WindFolder::mk(ROOT . 'back' . SLASH . 'code' . $dir) or WindFolder::mkRecur(ROOT . 'back' . SLASH . 'code' . $dir);
				}
				$this->jump('原程序目录备份完成~', 'file', array('step' => 2));
				break;
		}
	}

	public function backAction() {
		// #所有的都处理完成~就恢复即可~
		$database = include ROOT . 'conf' . SLASH . 'dataBase.php';
		$founder  = include ROOT . 'back' . SLASH . 'code' . SLASH . 'conf' . SLASH . 'founder.php';
		WindFile::savePhpData(PW_ROOT . 'conf' . SLASH . 'database.php', $database);
		WindFile::savePhpData(PW_ROOT . 'conf' . SLASH . 'founder.php', $founder);
		WindFile::write(PW_ROOT . 'data' . SLASH . 'install.lock', 'LOCKED');
		$message = '网站转换完成！<br>';
		$message .= '你的原网站程序代码放置在' . ROOT . 'back' . SLASH . 'code' . '目录下<br>';
		$message .= '你网站原来的数据~转码后UTF-8sql放置在' . ROOT . 'back' . SLASH . 'data' . '目录下<br>';
		$message .= '为了您网站的安全,建议你转换完成后删除本转换程序~';
		$this->show($message);
	}

	function newURL($step, array $params = array()) {
		$params['step']         = intval($step);
		$params['medz']         = 'medz';
		$_SERVER['REQUEST_URI'] = explode('?', $_SERVER['REQUEST_URI']);
		$_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI']['0'];
		$url  = '';
		$url .= ($_SERVER['REQUEST_SCHEME'] ? $_SERVER['REQUEST_SCHEME'] . ':' : '') . '//';
		$url .= $_SERVER['SERVER_NAME'] ? $_SERVER['SERVER_NAME'] : $_SERVER['HTTP_HOST'];
		$url .= ':' . ($_SERVER['SERVER_PORT'] ? $_SERVER['SERVER_PORT'] : '80');
		$url .= $_SERVER['REQUEST_URI'] ? $_SERVER['REQUEST_URI'] : $_SERVER['SCRIPT_NAME'];
		$url .= '?';
		foreach($params as $name => $value) {
			$url .= $name . '=' . urlencode($value);
			$url .= '&';
		}
		$url = explode('&', $url);
		$url = array_filter($url);
		$url = implode('&', $url);
		return $url;
	}

	private function SQL($sql, $autoIncrement = 1) {
		$autoIncrement = intval($autoIncrement);
		if(!preg_match("/;$/i", $sql)) {
			$sql .= ';';
		}
		$sql .= "\n\r\n";
		$sql = preg_replace("/CHARSET([ ]*?)=([ ]*?\w+\d+)/is", 'CHARSET=utf8', $sql);
		$sql = preg_replace("/CHARSET([ ]*?)=([ ]*?\w+)/is", 'CHARSET=utf8', $sql);
		$sql = preg_replace("/AUTO_INCREMENT([ ]*?)=([ ]*?\d+)/is", 'AUTO_INCREMENT=' . $autoIncrement, $sql);
		return $sql;
	}

	// #url组装方法
	function URL($action, array $params = array()) {
		return WindUrlHelper::createUrl($action, $params);
	}

	// #页面跳转方法~
	function jump($message, $action, array $params = array()) {
		$url = $this->URL($action, $params);
		$this->show($message, $url);
	}

	// #显示消息
	function show($message = '', $url = null) {
		include ROOT . 'show.php';
		exit;
	}

	function getDir($dir, array $arr = array(), array $not = array()) {
		$this->notFileDir = array_merge($this->notFileDir, $not);
		if($handle = opendir($dir)) {
			while(($file = readdir($handle)) !== false) {
				if($file != "." and $file != ".." and is_dir($dir . SLASH . $file)) {
					if(in_array($dir . SLASH . $file, $this->notFileDir)) {
						continue;
					}
					$arr[] = $dir . SLASH . $file;
					$arr = $this->getDir($dir . SLASH . $file, $arr);
				}
			}
		}
		return $arr;
	}

	function getFile($dir, array $arr = array(), array $not = array()) {
		$this->notFileDir = array_merge($this->notFileDir, $not);
		if($handle = opendir($dir)) {
			while(($file = readdir($handle)) !== false) {
				if($file != "." and $file != "..") {
					$file = $dir . SLASH . $file;
					if(in_array($file, $this->notFileDir)) {
						continue;
					}
					if(is_file($file)) {
						$arr[] = $file;
					} else if(is_dir($file)) {
						$arr = $this->getFile($file, $arr);
					}
				}
			}
		}
		return $arr;
	}

	public function db() {
		$db = Wind::getComponent('db');
		$db->query("SET NAMES utf8");
		return $db;
	}

}

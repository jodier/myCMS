<?php
/*-------------------------------------------------------------------------*/

ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(-1);

/*-------------------------------------------------------------------------*/

require_once('config.php');
require_once('php/parsedown.php');

/*-------------------------------------------------------------------------*/

class Extension extends Parsedown
{
	/*-----------------------------------------------------------------*/

	function __construct()
	{
		$this->InlineTypes['$'] []= 'Latex';

		$this->inlineMarkerList .= '$';
	}

	/*-----------------------------------------------------------------*/

	protected function inlineLatex($Excerpt)
	{
		if(preg_match('/^\$\$[^\$]+\$\$/', $Excerpt['text'], $matches)
		   ||
		   preg_match( '/^\$([^\$]+)\$/' , $Excerpt['text'], $matches)
		 ) {
			return array(
				'extent' => strlen($matches[0]),
				'markup' => /*--*/($matches[0]),
			);
		}
	}

	/*-----------------------------------------------------------------*/

	protected function blockTable($Line, array $Block = null)
	{
		$Block = parent::blockTable($Line, $Block);

		if($Block != null)
		{
			$Block['element']['attributes'] = array(
				'class' => 'table table-striped'
			);

			return $Block;
		}
	}

	/*-----------------------------------------------------------------*/
}

/*-------------------------------------------------------------------------*/

class TMyCMS
{
	/*-----------------------------------------------------------------*/
	/*-----------------------------------------------------------------*/

	public $diskFree = 0;
	public $diskTotal = 0;

	public $memFree = 0;
	public $memTotal = 0;

	/*-----------------------------------------------------------------*/

	public function __construct($host, $port, $db, $login, $password, $adminIPs)
	{
		/*----------------------------------------------------------*/

		$dir = dirname(__FILE__);

		$this->diskFree = disk_free_space($dir);

		$this->diskTotal = disk_total_space($dir);

		/*----------------------------------------------------------*/

		$fp = @fopen('/proc/meminfo', 'r');

		if($fp)
		{
			while($line = fgets($fp))
			{
				$pieces = array();

				/**/ if(preg_match('/^MemFree:\s+(\d+)\skB$/', $line, $pieces))
				{
					$this->memFree = $pieces[1];
				}
				else if(preg_match('/^MemTotal:\s+(\d+)\skB$/', $line, $pieces))
				{
					$this->memTotal = $pieces[1];
				}
			}

			fclose($fp);
		}

		/*----------------------------------------------------------*/

		try
		{
			$this->pdo = new PDO("mysql:host=$host;port=$port;dbname=$db", $login, $password);

			$this->pdo->exec('SET NAMES "utf8"');

			$this->adminIPs = $adminIPs;

			$this->error = '';
		}
 		catch(Exception $e)
		{
			die('<html><body>database error</body></html>');
		}

		/*----------------------------------------------------------*/
	}

	/*-----------------------------------------------------------------*/
	/*-----------------------------------------------------------------*/

	public function isGuest()
	{
		return isset($_SERVER['HTTP_CLIENT_IP']) || isset($_SERVER['HTTP_X_FORWARDED_FOR']) || in_array(@$_SERVER['REMOTE_ADDR'], $this->adminIPs) === false || php_sapi_name() === 'cli-server';
	}

	/*-----------------------------------------------------------------*/

	public function getDiskUsage()
	{
		return $this->diskFree !== 0 ? 100.0 * (1.0 - $this->diskFree / $this->diskTotal) : 100.0;
	}

	/*-----------------------------------------------------------------*/

	public function getMemUsage()
	{
		return $this->memFree !== 0 ? 100.0 * (1.0 - $this->memFree / $this->memTotal) : 100.0;
	}

	/*-----------------------------------------------------------------*/

	public function hasParam($name)
	{
		/**/ if(isset($_GET[$name])) {
			$result = true;
		}
		else if(isset($_POST[$name])) {
			$result = true;
		}
		else {
			$result = false;
		}

		return $result;
	}

	/*-----------------------------------------------------------------*/

	public function getParam($name, $default = '')
	{
		/**/ if(isset($_GET[$name])) {
			$result = $_GET[$name];
		}
		else if(isset($_POST[$name])) {
			$result = $_POST[$name];
		}
		else {
			$result = $default;
		}

		return $result;
	}

	/*-----------------------------------------------------------------*/

	public function escapeSQL($s)
	{
		$result = '';

		for($i = 0; $i < strlen($s); $i++)
		{
			switch($c = $s[$i])
			{
				case '\'':
					$result .= '\'\'';
					break;

				case "\\":
					$result .= '\\\\';
					break;

				case "\n":
					$result .= '\n';
					break;

				case "\r":
					$result .= '\r';
					break;

				default:
					$result .= $c;
			}
		}

		return $result;
	}

	/*-----------------------------------------------------------------*/

	public function escapeHTML($s)
	{
		$result = '';

		for($i = 0; $i < strlen($s); $i++)
		{
			switch($c = $s[$i])
			{
				case '"':
					$result .= '&quot;';
					break;

				case '<':
					$result .= '&lt;';
					break;

				case '>':
					$result .= '&gt;';
					break;

				default:
					$result .= $c;
			}
		}

		return $result;
	}

	/*-----------------------------------------------------------------*/

	public function errorInfo()
	{
		return $this->escapeHTML($this->pdo->errorInfo()[2] . $this->error);
	}

	/*-----------------------------------------------------------------*/
	/*-----------------------------------------------------------------*/

	public function setupDB()
	{
		if($this->isGuest())
		{
			die('<html><body>not authorized</body></html>');
		}

		try
		{
			$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

			$this->pdo->exec(
				'CREATE TABLE `categories` (' .
				'  `id` int(11) NOT NULL,' .
				'  `alias` varchar(128) NOT NULL,' .
				'  `title` varchar(128) NOT NULL,' .
				'  `icon` varchar(128) NOT NULL,' .
				'  `rank` int(11) NOT NULL DEFAULT \'0\'' .
				') ENGINE=InnoDB DEFAULT CHARSET=utf8'
			);

			$this->pdo->exec(
				'CREATE TABLE `pages` (' .
				'  `id` int(11) NOT NULL,' .
				'  `alias` varchar(128) NOT NULL,' .
				'  `title` varchar(128) NOT NULL,' .
				'  `content` text NOT NULL,' .
				'  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,' .
				'  `visible` int(1) NOT NULL DEFAULT \'0\'' .
				') ENGINE=InnoDB DEFAULT CHARSET=utf8;'
			);

			$this->pdo->exec(
				'CREATE TABLE `articles` (' .
				'  `id` int(11) NOT NULL,' .
				'  `alias` varchar(128) NOT NULL,' .
				'  `category` int(11) NOT NULL,' .
				'  `title` varchar(128) NOT NULL,' .
				'  `content` text NOT NULL,' .
				'  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,' .
				'  `visible` int(1) NOT NULL DEFAULT \'0\'' .
				') ENGINE=InnoDB DEFAULT CHARSET=utf8;'
			);

			$this->pdo->exec(
				'CREATE TABLE `menus` (' .
				'  `id` int(11) NOT NULL,' .
				'  `alias` varchar(128) NOT NULL,' .
				'  `category` int(11) NOT NULL,' .
				'  `parent` int(11) NULL,' .
				'  `title` varchar(128) NOT NULL,' .
				'  `icon` varchar(128) NOT NULL,' .
				'  `rank` int(11) NOT NULL DEFAULT \'0\',' .
				'  `page` int(11) NOT NULL,' .
				'  `visible` int(1) NOT NULL DEFAULT \'1\'' .
				') ENGINE=InnoDB DEFAULT CHARSET=utf8'
			);

			$this->pdo->exec(
				'CREATE VIEW articlesV AS' .
				' SELECT articles.id AS id, articles.alias AS alias, categories.alias AS category, articles.title AS title, articles.content AS content, articles.date AS date, articles.visible AS visible FROM categories, articles' .
				' WHERE articles.category=categories.id'
			);

			$this->pdo->exec(
				'CREATE VIEW menusV AS' .
				' SELECT menus.id, menus.alias, categories.alias AS category, CASE WHEN menus.parent IS NOT NULL THEN (SELECT T.alias FROM menus AS T WHERE T.id=menus.parent) ELSE \'\' END AS parent, menus.title, menus.icon, categories.rank AS rank0, menus.rank, pages.alias AS page, menus.visible FROM categories, menus, pages' .
				' WHERE menus.category=categories.id AND menus.page=pages.id'
			);

			$this->pdo->exec('ALTER TABLE `categories` ADD UNIQUE KEY `id` (`id`), ADD KEY `IDX1` (`id`);');
			$this->pdo->exec('ALTER TABLE `pages` ADD UNIQUE KEY `id` (`id`), ADD KEY `IDX2` (`id`);');
			$this->pdo->exec('ALTER TABLE `articles` ADD UNIQUE KEY `id` (`id`), ADD KEY `IDX3` (`id`);');
			$this->pdo->exec('ALTER TABLE `menus` ADD UNIQUE KEY `id` (`id`), ADD KEY `IDX4` (`id`);');

			$this->pdo->exec('ALTER TABLE `categories` ADD UNIQUE KEY `alias` (`alias`), ADD KEY `IDX6` (`alias`);');
			$this->pdo->exec('ALTER TABLE `pages` ADD UNIQUE KEY `alias` (`alias`), ADD KEY `IDX7` (`alias`);');
			$this->pdo->exec('ALTER TABLE `articles` ADD UNIQUE KEY `alias` (`alias`), ADD KEY `IDX8` (`alias`);');
			$this->pdo->exec('ALTER TABLE `menus` ADD UNIQUE KEY `alias` (`alias`), ADD KEY `IDX9` (`id`);');

			$this->pdo->exec('ALTER TABLE `categories` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=0;');
			$this->pdo->exec('ALTER TABLE `pages` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=0;');
			$this->pdo->exec('ALTER TABLE `articles` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=0;');
			$this->pdo->exec('ALTER TABLE `menus` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=0;');

			$this->pdo->exec('ALTER TABLE `articles` ADD CONSTRAINT `FK1` FOREIGN KEY (`category`) REFERENCES `categories` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;');
			$this->pdo->exec('ALTER TABLE `menus` ADD CONSTRAINT `FK2` FOREIGN KEY (`category`) REFERENCES `categories` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;');
			$this->pdo->exec('ALTER TABLE `menus` ADD CONSTRAINT `FK3` FOREIGN KEY (`parent`) REFERENCES `menus` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;');
			$this->pdo->exec('ALTER TABLE `menus` ADD CONSTRAINT `FK4` FOREIGN KEY (`page`) REFERENCES `pages` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;');

			die('<html><body>done with success</body></html>');
		}
 		catch(Exception $e)
		{
			die("<html><body><pre>$e</pre></body></html>");
		}
	}

	/*-----------------------------------------------------------------*/
	/*-----------------------------------------------------------------*/

	public function upgradeCMS()
	{
		if($this->isGuest())
		{
			die('<html><body>not authorized</body></html>');
		}

		/*---------------------------------------------------------*/

		$temp_dir = sys_get_temp_dir();

		/*---------------------------------------------------------*/

		$fp = fopen('https://github.com/jodier/myCMS/archive/master.zip', 'r');

		if($fp === FALSE)
		{
			die('<html><body>could not download myCMS</body></html>');
		}

		/*---------------------------------------------------------*/

		$nb = file_put_contents("$temp_dir/myCMS-master.zip", $fp);

		if($nb === FALSE)
		{
			die('<html><body>could not write myCMS</body></html>');
		}

		/*---------------------------------------------------------*/

		$stdout = shell_exec("unzip -o -d $temp_dir $temp_dir/myCMS-master.zip && cp -Rv $temp_dir/myCMS-master/* .");

		/*---------------------------------------------------------*/

		die("<html><body><pre>$stdout</pre>done with success ($nb bytes)</body></html>");
	}

	/*-----------------------------------------------------------------*/
	/*-----------------------------------------------------------------*/

	public function addCategory()
	{
		if($this->isGuest())
		{
			$this->error = 'not authorized';

			return;
		}

		/*---------------------------------------------------------*/

		$alias = $this->escapeSQL($this->getParam('categoryAlias'));
		$title = $this->escapeSQL($this->getParam('categoryTitle'));
		$rank = $this->escapeSQL($this->getParam('categoryRank'));

		if($alias === '' || $title === '' || $rank === '')
		{
			$this->error = 'missing parameter(s)';

			return;
		}

		/*---------------------------------------------------------*/

		$this->pdo->exec("INSERT INTO categories (alias, title, rank) VALUES ('$alias', '$title', '$rank')");

		/*---------------------------------------------------------*/
	}

	/*-----------------------------------------------------------------*/

	public function updateCategory()
	{
		if($this->isGuest())
		{
			$this->error = 'not authorized';

			return;
		}

		/*---------------------------------------------------------*/

		$id = $this->escapeSQL($this->getParam('updateCategory'));
		$alias = $this->escapeSQL($this->getParam('categoryAlias'));
		$title = $this->escapeSQL($this->getParam('categoryTitle'));
		$rank = $this->escapeSQL($this->getParam('categoryRank'));

		if($id === '' || $alias === '' || $title === '' || $rank === '')
		{
			$this->error = 'missing parameter(s)';

			return;
		}

		/*---------------------------------------------------------*/

		$this->pdo->exec("UPDATE categories SET alias='$alias', title='$title', rank='$rank' WHERE id='$id'");

		/*---------------------------------------------------------*/
	}

	/*-----------------------------------------------------------------*/

	public function delCategory()
	{
		if($this->isGuest())
		{
			$this->error = 'not authorized';

			return;
		}

		/*---------------------------------------------------------*/

		$id = $this->escapeSQL($this->getParam('delCategory'));

		/*---------------------------------------------------------*/

		$this->pdo->exec("DELETE FROM categories WHERE id = '$id'");

		/*---------------------------------------------------------*/
	}

	/*-----------------------------------------------------------------*/

	public function addPage()
	{
		if($this->isGuest())
		{
			$this->error = 'not authorized';

			return;
		}

		/*---------------------------------------------------------*/

		$alias = $this->escapeSQL($this->getParam('pageAlias'));
		$title = $this->escapeSQL($this->getParam('pageTitle'));

		if($alias === '' || $title === '')
		{
			$this->error = 'missing parameter(s)';

			return;
		}

		/*---------------------------------------------------------*/

		$this->pdo->exec("INSERT INTO pages (alias, title, visible) VALUES ('$alias', '$title', 0)");

		/*---------------------------------------------------------*/
	}

	/*-----------------------------------------------------------------*/

	public function updatePage()
	{
		if($this->isGuest())
		{
			$this->error = 'not authorized';

			return;
		}

		/*---------------------------------------------------------*/

		$id = $this->escapeSQL($this->getParam('updatePage'));
		$alias = $this->escapeSQL($this->getParam('pageAlias'));
		$title = $this->escapeSQL($this->getParam('pageTitle'));
		$content = $this->escapeSQL($this->getParam('pageContent'));
		$visible = $this->escapeSQL($this->getParam('pageVisible', '0'));

		if($id === '' || $alias === '' || $title === '' || $visible === '')
		{
			$this->error = 'missing parameter(s)';

			return;
		}

		/*---------------------------------------------------------*/

		$this->pdo->exec("UPDATE pages SET alias='$alias', title='$title', content='$content', visible='$visible' WHERE id='$id'");

		/*---------------------------------------------------------*/
	}

	/*-----------------------------------------------------------------*/

	public function delPage()
	{
		if($this->isGuest())
		{
			$this->error = 'not authorized';

			return;
		}

		/*---------------------------------------------------------*/

		$id = $this->escapeSQL($this->getParam('delPage'));

		/*---------------------------------------------------------*/

		$this->pdo->exec("DELETE FROM pages WHERE id = '$id'");

		/*---------------------------------------------------------*/
	}

	/*-----------------------------------------------------------------*/

	public function addArticle()
	{
		if($this->isGuest())
		{
			$this->error = 'not authorized';

			return;
		}

		/*---------------------------------------------------------*/

		$alias = $this->escapeSQL($this->getParam('articleAlias'));
		$category = $this->escapeSQL($this->getParam('articleCategory'));
		$title = $this->escapeSQL($this->getParam('articleTitle'));

		if($alias === '' || $category === '' || $title === '')
		{
			$this->error = 'missing parameter(s)';

			return;
		}

		/*---------------------------------------------------------*/

		$this->pdo->exec("INSERT INTO articles (alias, category, title, visible) VALUES ('$alias', '$category', '$title', 0)");

		/*---------------------------------------------------------*/
	}

	/*-----------------------------------------------------------------*/

	public function updateArticle()
	{
		if($this->isGuest())
		{
			$this->error = 'not authorized';

			return;
		}

		/*---------------------------------------------------------*/

		$id = $this->escapeSQL($this->getParam('updateArticle'));
		$alias = $this->escapeSQL($this->getParam('articleAlias'));
		$category = $this->escapeSQL($this->getParam('articleCategory'));
		$title = $this->escapeSQL($this->getParam('articleTitle'));
		$content = $this->escapeSQL($this->getParam('articleContent'));
		$visible = $this->escapeSQL($this->getParam('articleVisible', '0'));

		if($id === '' || $alias === '' || $category === '' || $title === '' || $visible === '')
		{
			$this->error = 'missing parameter(s)';

			return;
		}

		/*---------------------------------------------------------*/

		$this->pdo->exec("UPDATE articles SET alias='$alias', category='$category', title='$title', content='$content', visible='$visible' WHERE id='$id'");

		/*---------------------------------------------------------*/
	}

	/*-----------------------------------------------------------------*/

	public function delArticle()
	{
		if($this->isGuest())
		{
			$this->error = 'not authorized';

			return;
		}

		/*---------------------------------------------------------*/

		$id = $this->escapeSQL($this->getParam('delArticle'));

		/*---------------------------------------------------------*/

		$this->pdo->exec("DELETE FROM articles WHERE id = '$id'");

		/*---------------------------------------------------------*/
	}

	/*-----------------------------------------------------------------*/

	public function addMenu()
	{
		if($this->isGuest())
		{
			$this->error = 'not authorized';

			return;
		}

		/*---------------------------------------------------------*/

		$alias = $this->escapeSQL($this->getParam('menuAlias'));
		$category = $this->escapeSQL($this->getParam('menuCategory'));
		$parent = $this->escapeSQL($this->getParam('menuParent'));
		$title = $this->escapeSQL($this->getParam('menuTitle'));
		$rank = $this->escapeSQL($this->getParam('menuRank'));
		$page = $this->escapeSQL($this->getParam('menuPage'));

		if($alias === '' || $category === '' || $title === '' || $rank === '' || $page === '')
		{
			$this->error = 'missing parameter(s)';

			return;
		}

		/*---------------------------------------------------------*/

		if($parent === '') {
			$this->pdo->exec("INSERT INTO menus (alias, category, parent, title, rank, page) VALUES ('$alias', '$category', NULL, '$title', '$rank', '$page')");
		}
		else {
			$this->pdo->exec("INSERT INTO menus (alias, category, parent, title, rank, page) VALUES ('$alias', '$category', '$parent', '$title', '$rank', '$page')");
		}

		/*---------------------------------------------------------*/
	}

	/*-----------------------------------------------------------------*/

	public function updateMenu()
	{
		if($this->isGuest())
		{
			$this->error = 'not authorized';

			return;
		}

		/*---------------------------------------------------------*/

		$id = $this->escapeSQL($this->getParam('updateMenu'));
		$alias = $this->escapeSQL($this->getParam('menuAlias'));
		$category = $this->escapeSQL($this->getParam('menuCategory'));
		$parent = $this->escapeSQL($this->getParam('menuParent'));
		$title = $this->escapeSQL($this->getParam('menuTitle'));
		$rank = $this->escapeSQL($this->getParam('menuRank'));
		$page = $this->escapeSQL($this->getParam('menuPage'));
		$visible = $this->escapeSQL($this->getParam('menuVisible', '0'));

		if($id === '' || $alias === '' || $category === '' || $title === '' || $rank === '' || $page === '' || $visible === '')
		{
			$this->error = 'missing parameter(s)';

			return;
		}

		/*---------------------------------------------------------*/

		if($parent === '') {
			$this->pdo->exec("UPDATE menus SET alias='$alias', category='$category', parent=NULL, title='$title', rank='$rank', page='$page', visible='$visible' WHERE id='$id'");
		}
		else {
			$this->pdo->exec("UPDATE menus SET alias='$alias', category='$category', parent='$parent', title='$title', rank='$rank', page='$page', visible='$visible' WHERE id='$id'");
		}

		/*---------------------------------------------------------*/
	}

	/*-----------------------------------------------------------------*/

	public function delMenu()
	{
		if($this->isGuest())
		{
			$this->error = 'not authorized';

			return;
		}

		/*---------------------------------------------------------*/

		$id = $this->escapeSQL($this->getParam('delMenu'));

		/*---------------------------------------------------------*/

		$this->pdo->exec("DELETE FROM menus WHERE id = '$id'");

		/*---------------------------------------------------------*/
	}

	/*-----------------------------------------------------------------*/
	/*-----------------------------------------------------------------*/

	private function compressImage($fileName)
	{
		if(extension_loaded('imagick'))
		{
			$extension = pathinfo($fileName)['extension'];

			if($extension === 'jpg'
			   ||
			   $extension === 'png'
			 ) {
				try
				{
					$im = new Imagick();

					$im->readImage($fileName);
					$im->thumbnailImage(400, 300, true);
					$im->writeImage(substr($fileName, 0, -4) . '_thumb.' . $extension);
					$im->destroy();
				}
				catch(Exception $e)
				{
					$this->error = "could not create thumbnail image: " . $e->getMessage();
				}
			}
		}
		else
		{
			$this->error = "could not create thumbnail image: extension `imagick` not installed";
		}
	}

	/*-----------------------------------------------------------------*/

	public function addFile()
	{
		if($this->isGuest() || isset($_FILES['files']) === false)
		{
			$this->error = 'not authorized';

			return;
		}

		/*---------------------------------------------------------*/

		$files = $_FILES['files'];

		$nr = count($files['name']);

		for($i = 0; $i < $nr; $i++)
		{
			$from = $files['tmp_name'][$i];
			$to = $files['name'][$i];

			if(copy("$from", "../media/$to"))
			{
				$this->compressImage("../media/$to");
			}
			else
			{
				$this->error = "could not copy `$from` to `../media/$to`";
			}
		}

		/*---------------------------------------------------------*/
	}

	/*-----------------------------------------------------------------*/

	public function renFile()
	{
		if($this->isGuest() || $this->hasParam('oldFile') === false || $this->hasParam('newFile') === false)
		{
			$this->error = 'not authorized';

			return;
		}

		/*---------------------------------------------------------*/

		$oldFile = $this->getParam('oldFile');
		$newFile = $this->getParam('newFile');

		if(rename("../media/$oldFile", "../media/$newFile") === false)
		{
			$this->error = "could not rename `$oldFile` to `$newFile`";
		}

		/*---------------------------------------------------------*/
	}

	/*-----------------------------------------------------------------*/

	public function delFile()
	{
		if($this->isGuest() || $this->hasParam('file') === false)
		{
			$this->error = 'not authorized';

			return;
		}

		/*---------------------------------------------------------*/

		$file = $this->getParam('file');

		if(unlink("../media/$file") === false)
		{
			$this->error = "could not delete `$file`";
		}

		/*---------------------------------------------------------*/
	}

	/*-----------------------------------------------------------------*/
	/*-----------------------------------------------------------------*/

	private function _buildWhereClause($opts)
	{
		$i = 0;
		$L = [];

		foreach($opts as $key => $val)
		{
			$i++;

			array_push($L, "$key='" . $this->escapeSQL($val) . "'");
		}

		return $i > 0 ? ' WHERE ' . join($L, ' AND ') : '';
	}

	/*-----------------------------------------------------------------*/

	public function getCategories($opts = [])
	{
		/*---------------------------------------------------------*/

		$stmt = $this->pdo->query('SELECT * FROM categories' . $this->_buildWhereClause($opts) . ' ORDER BY rank, alias ASC');

		/*---------------------------------------------------------*/

		$result = $stmt->fetchAll();

		/*---------------------------------------------------------*/

		$stmt->closeCursor();

		/*---------------------------------------------------------*/

		return $result;
	}

	/*-----------------------------------------------------------------*/

	public function getPages($opts = [])
	{
		/*---------------------------------------------------------*/

		$stmt = $this->pdo->query('SELECT * FROM pages' . $this->_buildWhereClause($opts) . ' ORDER BY alias ASC');

		/*---------------------------------------------------------*/

		$result = $stmt->fetchAll();

		/*---------------------------------------------------------*/

		$stmt->closeCursor();

		/*---------------------------------------------------------*/

		return $result;
	}

	/*-----------------------------------------------------------------*/

	public function getArticles($opts = [])
	{
		/*---------------------------------------------------------*/

		$stmt = $this->pdo->query('SELECT * FROM articlesV' . $this->_buildWhereClause($opts) . ' ORDER BY category, alias ASC');

		/*---------------------------------------------------------*/

		$result = $stmt->fetchAll();

		/*---------------------------------------------------------*/

		$stmt->closeCursor();

		/*---------------------------------------------------------*/

		return $result;
	}
	
	/*-----------------------------------------------------------------*/

	public function getMenus($opts = [])
	{
		/*---------------------------------------------------------*/

		$stmt = $this->pdo->query('SELECT * FROM menusV' . $this->_buildWhereClause($opts) . ' ORDER BY rank0, rank, alias ASC');

		/*---------------------------------------------------------*/

		$result = $stmt->fetchAll();

		/*---------------------------------------------------------*/

		$stmt->closeCursor();

		/*---------------------------------------------------------*/

		return $result;
	}

	/*-----------------------------------------------------------------*/
	/*-----------------------------------------------------------------*/

	public function getCategoryJson()
	{
		/*---------------------------------------------------------*/

		$id = $this->getParam('getCategoryJson');

		/*---------------------------------------------------------*/

		$stmt = $this->pdo->query("SELECT * FROM categories WHERE id='$id'");

		/*---------------------------------------------------------*/

		while($category = $stmt->fetch())
		{
			$stmt->closeCursor();

			die(json_encode($category));
		}

		/*---------------------------------------------------------*/

		$stmt->closeCursor();

		die('{}');

		/*---------------------------------------------------------*/
	}

	/*-----------------------------------------------------------------*/

	public function getPageJson()
	{
		/*---------------------------------------------------------*/

		$id = $this->getParam('getPageJson');

		/*---------------------------------------------------------*/

		$stmt = $this->pdo->query("SELECT * FROM pages WHERE id='$id'");

		/*---------------------------------------------------------*/

		while($page = $stmt->fetch())
		{
			$stmt->closeCursor();

			die(json_encode($page));
		}

		/*---------------------------------------------------------*/

		$stmt->closeCursor();

		die('{}');

		/*---------------------------------------------------------*/
	}

	/*-----------------------------------------------------------------*/

	public function getArticleJson()
	{
		/*---------------------------------------------------------*/

		$id = $this->getParam('getArticleJson');

		/*---------------------------------------------------------*/

		$stmt = $this->pdo->query("SELECT * FROM articles WHERE id='$id'");

		/*---------------------------------------------------------*/

		while($articles = $stmt->fetch())
		{
			$stmt->closeCursor();

			die(json_encode($articles));
		}

		/*---------------------------------------------------------*/

		$stmt->closeCursor();

		die('{}');

		/*---------------------------------------------------------*/
	}

	/*-----------------------------------------------------------------*/

	public function getMenuJson()
	{
		/*---------------------------------------------------------*/

		$id = $this->getParam('getMenuJson');

		/*---------------------------------------------------------*/

		$stmt = $this->pdo->query("SELECT * FROM menus WHERE id='$id'");

		/*---------------------------------------------------------*/

		while($menu = $stmt->fetch())
		{
			$stmt->closeCursor();

			die(json_encode($menu));
		}

		/*---------------------------------------------------------*/

		$stmt->closeCursor();

		die('{}');

		/*---------------------------------------------------------*/
	}

	/*-----------------------------------------------------------------*/
	/*-----------------------------------------------------------------*/

	public function getDoc()
	{
		if($this->hasParam('q') === false
		   &&
		   $this->hasParam('page') === false
		   &&
		   $this->hasParam('pages') === false
		   &&
		   $this->hasParam('article') === false
		   &&
		   $this->hasParam('articles') === false
		   &&
		   $this->hasParam('category') === false
		   &&
		   $this->hasParam('categories') === false
		 ) {
			$_GET['pages'] = 'home';
		}

		/*---------------------------------------------------------*/
		/* SEARCH                                                  */
		/*---------------------------------------------------------*/

		/**/ if($this->hasParam('q'))
		{
			$i = 0;
			$result = '';
			$q = $this->getParam('q');
			$Q1 = $this->escapeSQL($q);
			$Q2 = $this->escapeHTML($q);

			$stmt = $this->pdo->query("SELECT alias, title FROM pages WHERE (alias='$Q1' OR title LIKE '%{$Q1}%' OR content LIKE '%{$Q1}%') AND visible!=0");

			while($line = $stmt->fetch())
			{
				$alias = $this->escapeHTML($line['alias']);
				$title = $this->escapeHTML($line['title']);

				$result = "$result<tr>";
				$result = "$result<td>page</td>";
				$result = "$result<td><a href=\"/pages/$alias\">$title</a></td>";
				$result = "$result</tr>";

				$i++;
			}

			$stmt = $this->pdo->query("SELECT alias, title FROM articles WHERE (alias='$Q1' OR title LIKE '%{$Q1}%' OR content LIKE '%{$Q1}%') AND visible!=0");

			while($line = $stmt->fetch())
			{
				$alias = $this->escapeHTML($line['alias']);
				$title = $this->escapeHTML($line['title']);

				$result = "$result<tr>";
				$result = "$result<td>article</td>";
				$result = "$result<td><a href=\"/articles/$alias\">$title</a></td>";
				$result = "$result</tr>";

				$i++;
			}

			return [
				'path' => '<li class="active">Search</li>',
				'title' => "Search « $Q2 »",
				'content' => "<table class=\"table\"><thead><tr><td>Type</td><td>Title</td></tr></thead><tbody>$result</tbody></table><blockquote>$i matching result(s)</blockquote>",
				'date' => '',
			];
		}

		/*---------------------------------------------------------*/
		/* CATEGORIES                                              */
		/*---------------------------------------------------------*/

		else if($this->hasParam('categories'))
		{
			$i = 0;
			$result = '';
			$categories = $this->getParam('categories');
			$CATEGORIES = $this->escapeHTML($categories);

			if($categories === '')
			{
				foreach($this->getCategories() as $category)
				{
					$alias = $this->escapeHTML($category['alias']);
					$title = $this->escapeHTML($category['title']);

					$result = "$result<tr>";
					$result = "$result<td><a href=\"/categories/$alias\">$title</a></td>";
					$result = "$result</tr>";

					$i++;
				}

				return [
					'path' => '<li class="active">categories</li>',
					'title' => 'Categories',
					'content' => "<table class=\"table\"><thead><tr><td>Title</td></tr></thead><tbody>$result</tbody></table><blockquote>$i matching result(s)</blockquote>",
					'date' => '',
				];
			}
			else
			{
				foreach($this->getArticles(['category' => $categories, 'visible' => '1']) as $article)
				{
					$alias = $this->escapeHTML($article['alias']);
					$title = $this->escapeHTML($article['title']);

					$result = "$result<tr>";
					$result = "$result<td><a href=\"/articles/$alias\">$title</a></td>";
					$result = "$result</tr>";

					$i++;
				}

				return [
					'path' => '<li class="active">categories</li>',
					'title' => "Category « $CATEGORIES »",
					'content' => "<table class=\"table\"><thead><tr><td>Title</td></tr></thead><tbody>$result</tbody></table><blockquote>$i matching result(s)</blockquote>",
					'date' => '',
				];
			}
		}

		/*---------------------------------------------------------*/
		/* PAGES                                                   */
		/*---------------------------------------------------------*/

		else if($this->hasParam('pages'))
		{
			$pages = $this->getParam('pages');
			$PAGES = $this->escapeSQL($pages);

			if($pages === '')
			{
				$i = 0;
				$result = '';

				foreach($this->getPages(['visible' => '1']) as $page)
				{
					$alias = $this->escapeHTML($page['alias']);
					$title = $this->escapeHTML($page['title']);

					$result = "$result<tr>";
					$result = "$result<td><a href=\"/pages/$alias\">$title</a></td>";
					$result = "$result</tr>";

					$i++;
				}

				return [
					'path' => '<li class="active">pages</li>',
					'title' => 'Pages',
					'content' => "<table class=\"table\"><thead><tr><td>Title</td></tr></thead><tbody>$result</tbody></table><blockquote>$i matching result(s)</blockquote>",
					'date' => '',
				];
			}
			else
			{
				$stmt = $this->pdo->query("SELECT id, alias, title, content, DATE_FORMAT(date,'%m-%d-%Y') AS date FROM pages WHERE (id='$PAGES' OR alias='$PAGES') AND visible!=0");

				while($line = $stmt->fetch())
				{
					return [
						'path' => "<li><a href=\"/pages\">pages</a></li><li class=\"active\">{$line['alias']}</li>",
						'title' => $line['title'],
						'content' => (new Extension())->text($line['content']),
						'date' => $line['date'],
					];
				}
			}
		}

		/*---------------------------------------------------------*/
		/* ARTICLES                                                */
		/*---------------------------------------------------------*/

		else if($this->hasParam('articles'))
		{
			$articles = $this->getParam('articles');
			$ARTICLES = $this->escapeSQL($articles);

			if($articles === '')
			{
				$i = 0;
				$result = '';

				foreach($this->getArticles(['visible' => '1']) as $article)
				{
					$category = $this->escapeHTML($article['category']);
					$alias = $this->escapeHTML($article['alias']);
					$title = $this->escapeHTML($article['title']);

					$result = "$result<tr>";
					$result = "$result<td><a href=\"/categories/$category\">$category</td>";
					$result = "$result<td><a href=\"/articles/$alias\">$title</a></td>";
					$result = "$result</tr>";

					$i++;
				}

				return [
					'path' => '<li class="active">articles</li>',
					'title' => 'Articles',
					'content' => "<table class=\"table\"><thead><tr><td>Category</td><td>Title</td></tr></thead><tbody>$result</tbody></table><blockquote>$i matching result(s)</blockquote>",
					'date' => '',
				];
			}
			else
			{
				$stmt = $this->pdo->query("SELECT id, alias, category, title, content, DATE_FORMAT(date,'%m-%d-%Y') AS date FROM articlesV WHERE (id='$ARTICLES' OR alias='$ARTICLES') AND visible!=0");

				while($line = $stmt->fetch())
				{
					return [
						'path' => "<li><a href=\"/articles\">articles</a></li><li><a href=\"/categories/{$line['category']}\">{$line['category']}</a></li><li class=\"active\">{$line['alias']}</li>",
						'title' => $line['title'],
						'content' => (new Extension())->text($line['content']),
						'date' => $line['date'],
					];
				}
			}
		}

		/*---------------------------------------------------------*/
		/* ERROR 404                                               */
		/*---------------------------------------------------------*/

		return [
			'path' => 'error 404',
			'title' => 'Error 404',
			'content' => 'Page not found...',
			'date' => '',
		];

		/*---------------------------------------------------------*/
	}

	/*-----------------------------------------------------------------*/
}

/*-------------------------------------------------------------------------*/

$cms = new TMyCMS($TMyCMS_host, $TMyCMS_port, $TMyCMS_db, $TMyCMS_login, $TMyCMS_password, $TMyCMS_adminIPs);

/*-------------------------------------------------------------------------*/

/**/ if($cms->hasParam('setupDB')) {
	$cms->setupDB();
}
else if($cms->hasParam('upgradeCMS')) {
	$cms->upgradeCMS();
}

/*-------------------------------------------------------------------------*/

else if($cms->hasParam('addCategory')) {
	$cms->addCategory();
}
else if($cms->hasParam('updateCategory')) {
	$cms->updateCategory();
}
else if($cms->hasParam('delCategory')) {
	$cms->delCategory();
}

else if($cms->hasParam('addPage')) {
	$cms->addPage();
}
else if($cms->hasParam('updatePage')) {
	$cms->updatePage();
}
else if($cms->hasParam('delPage')) {
	$cms->delPage();
}

else if($cms->hasParam('addArticle')) {
	$cms->addArticle();
}
else if($cms->hasParam('updateArticle')) {
	$cms->updateArticle();
}
else if($cms->hasParam('delArticle')) {
	$cms->delArticle();
}

else if($cms->hasParam('addMenu')) {
	$cms->addMenu();
}
else if($cms->hasParam('updateMenu')) {
	$cms->updateMenu();
}
else if($cms->hasParam('delMenu')) {
	$cms->delMenu();
}

/*-------------------------------------------------------------------------*/

else if($cms->hasParam('addFile')) {
	$cms->addFile();
}
else if($cms->hasParam('renFile')) {
	$cms->renFile();
}
else if($cms->hasParam('delFile')) {
	$cms->delFile();
}

/*-------------------------------------------------------------------------*/

else if($cms->hasParam('getCategoryJson')) {
	$cms->getCategoryJson();
}
else if($cms->hasParam('getPageJson')) {
	$cms->getPageJson();
}
else if($cms->hasParam('getArticleJson')) {
	$cms->getArticleJson();
}
else if($cms->hasParam('getMenuJson')) {
	$cms->getMenuJson();
}

/*-------------------------------------------------------------------------*/

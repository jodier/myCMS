<?php
/*-------------------------------------------------------------------------*/
/* DEBUG MODE                                                              */
/*-------------------------------------------------------------------------*/

ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(-1);

/*-------------------------------------------------------------------------*/
/* PARSEDOWN                                                               */
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

		if($Block)
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
/* MYCMS                                                                   */
/*-------------------------------------------------------------------------*/

class TMyCMS
{
	/*-----------------------------------------------------------------*/
	/*-----------------------------------------------------------------*/

	private $tarballURL = 'http://github.com/jodier/myCMS/archive/master.zip';

	/*-----------------------------------------------------------------*/
	/*-----------------------------------------------------------------*/

	public $diskFree = 0;
	public $diskTotal = 0;

	public $memFree = 0;
	public $memTotal = 0;

	/*-----------------------------------------------------------------*/
	/*-----------------------------------------------------------------*/

	public function __construct($host, $port, $db, $login, $password, $adminIPs)
	{
		/*----------------------------------------------------------*/

		$this->adminIPs = $adminIPs;

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
		}
 		catch(Exception $e)
		{
			$this->htmlError('database error');
		}

		/*----------------------------------------------------------*/

		$this->config = [];

		$stmt = $this->pdo->query('SELECT alias, content FROM config');

		if($stmt)
		{
			while($row = $stmt->fetch(PDO::FETCH_NUM))
	 		{
				$this->config[$row[0]] = $row[1];
			}

			$stmt->closeCursor();
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

	public function testHTTPS()
	{
		if((isset($this->config['force_https']) && $this->config['force_https'] === '1') && (!isset($_SERVER['HTTPS']) || !$_SERVER['HTTPS']))
		{
			header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);

			exit;
		}
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
	/*-----------------------------------------------------------------*/

	private function error($message)
	{
		die($message);
	}

	private function htmlError($message)
	{
		die('<html><body>' . $message . '</body></html>');
	}

	private function htmlErrorRedirect($message)
	{
		die('<html><head><meta http-equiv="Refresh" content="5; url=admin.php" /></head><body>' . $message . '</body></html>');
	}

	/*-----------------------------------------------------------------*/
	/*-----------------------------------------------------------------*/

	public function setupDB()
	{
		if($this->isGuest())
		{
			$this->htmlError('not authorized');
		}

		/*---------------------------------------------------------*/

		try
		{
			$this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

			$this->pdo->exec(
				'CREATE TABLE `config` (' .
				'  `id` int(11) NOT NULL,' .
				'  `alias` varchar(128) NOT NULL,' .
				'  `content` varchar(128) NOT NULL' .
				') ENGINE=InnoDB DEFAULT CHARSET=utf8'
			);

			$this->pdo->exec(
				'CREATE TABLE `categories` (' .
				'  `id` int(11) NOT NULL,' .
				'  `alias` varchar(128) NOT NULL,' .
				'  `title` varchar(128) NOT NULL,' .
				'  `icon` varchar(128) NOT NULL,' .
				'  `rank` int(11) NOT NULL DEFAULT \'0\',' .
				'  `visible` int(1) NOT NULL DEFAULT \'0\'' .
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
				'  `link` varchar(512) NULL,' .
				'  `visible` int(1) NOT NULL DEFAULT \'0\'' .
				') ENGINE=InnoDB DEFAULT CHARSET=utf8'
			);

			$this->pdo->exec(
				'CREATE VIEW articlesV AS' .
				' SELECT articles.id AS id, articles.alias AS alias, categories.alias AS category, articles.title AS title, articles.content AS content, articles.date AS date, articles.visible AS visible FROM categories, articles' .
				' WHERE articles.category=categories.id'
			);

			$this->pdo->exec(
				'CREATE VIEW menusV AS' .
				' SELECT menus.id, menus.alias, categories.alias AS category, CASE WHEN menus.parent IS NOT NULL THEN (SELECT T.alias FROM menus AS T WHERE T.id=menus.parent) ELSE \'\' END AS parent, menus.title, menus.icon, categories.rank AS rank0, menus.rank, CASE WHEN menus.link IS NOT NULL THEN menus.link ELSE CONCAT(\'/pages/\', pages.alias) END AS page, menus.visible FROM categories, menus, pages' .
				' WHERE menus.category=categories.id AND menus.page=pages.id'
			);

			$this->pdo->exec('ALTER TABLE `config` ADD UNIQUE KEY `id` (`id`), ADD KEY `IDX1` (`id`);');
			$this->pdo->exec('ALTER TABLE `categories` ADD UNIQUE KEY `id` (`id`), ADD KEY `IDX2` (`id`);');
			$this->pdo->exec('ALTER TABLE `pages` ADD UNIQUE KEY `id` (`id`), ADD KEY `IDX3` (`id`);');
			$this->pdo->exec('ALTER TABLE `articles` ADD UNIQUE KEY `id` (`id`), ADD KEY `IDX4` (`id`);');
			$this->pdo->exec('ALTER TABLE `menus` ADD UNIQUE KEY `id` (`id`), ADD KEY `IDX5` (`id`);');

			$this->pdo->exec('ALTER TABLE `config` ADD UNIQUE KEY `alias` (`alias`), ADD KEY `IDX6` (`alias`);');
			$this->pdo->exec('ALTER TABLE `categories` ADD UNIQUE KEY `alias` (`alias`), ADD KEY `IDX7` (`alias`);');
			$this->pdo->exec('ALTER TABLE `pages` ADD UNIQUE KEY `alias` (`alias`), ADD KEY `IDX8` (`alias`);');
			$this->pdo->exec('ALTER TABLE `articles` ADD UNIQUE KEY `alias` (`alias`), ADD KEY `IDX9` (`alias`);');
			$this->pdo->exec('ALTER TABLE `menus` ADD UNIQUE KEY `alias` (`alias`), ADD KEY `IDX10` (`alias`);');

			$this->pdo->exec('ALTER TABLE `config` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=0;');
			$this->pdo->exec('ALTER TABLE `categories` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=0;');
			$this->pdo->exec('ALTER TABLE `pages` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=0;');
			$this->pdo->exec('ALTER TABLE `articles` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=0;');
			$this->pdo->exec('ALTER TABLE `menus` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT,AUTO_INCREMENT=0;');

			$this->pdo->exec('ALTER TABLE `articles` ADD CONSTRAINT `FK1` FOREIGN KEY (`category`) REFERENCES `categories` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;');
			$this->pdo->exec('ALTER TABLE `menus` ADD CONSTRAINT `FK2` FOREIGN KEY (`category`) REFERENCES `categories` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;');
			$this->pdo->exec('ALTER TABLE `menus` ADD CONSTRAINT `FK3` FOREIGN KEY (`parent`) REFERENCES `menus` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;');
			$this->pdo->exec('ALTER TABLE `menus` ADD CONSTRAINT `FK4` FOREIGN KEY (`page`) REFERENCES `pages` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION;');

			$this->htmlErrorRedirect('done with success');
		}
 		catch(Exception $e)
		{
			$this->htmlErrorRedirect('<pre>' . $this->escapeHTML($e->getMessage()) . '</pre>done with error');
		}

		/*---------------------------------------------------------*/
	}

	/*-----------------------------------------------------------------*/
	/*-----------------------------------------------------------------*/

	public function upgradeCMS()
	{
		if($this->isGuest())
		{
			$this->htmlError('not authorized');
		}

		/*---------------------------------------------------------*/

		$fp = fopen($this->tarballURL, 'r');

		if($fp === false)
		{
			$this->htmlErrorRedirect('could not download myCMS');
		}

		/*---------------------------------------------------------*/

		$nb = file_put_contents('./tmp/myCMS-master.zip', $fp);

		if($nb === false)
		{
			$this->htmlErrorRedirect('could not write myCMS');
		}

		/*---------------------------------------------------------*/

		$stdout = shell_exec('unzip -o -d ./tmp ./tmp/myCMS-master.zip && cp -Rv ./tmp/myCMS-master/* . && rm -fr ./tmp/*');

		/*---------------------------------------------------------*/

		$this->htmlErrorRedirect('<pre>' . $this->escapeHTML($stdout) . '</pre>done with success');

		/*---------------------------------------------------------*/
	}

	/*-----------------------------------------------------------------*/
	/*-----------------------------------------------------------------*/

	public function addCategory()
	{
		if($this->isGuest())
		{
			$this->error('not authorized');
		}

		/*---------------------------------------------------------*/

		$alias = $this->getParam('categoryAlias');
		$title = $this->getParam('categoryTitle');
		$rank = $this->getParam('categoryRank');

		if($alias === '' || $title === '' || $rank === '')
		{
			$this->error('missing parameter(s)');
		}

		/*---------------------------------------------------------*/

		$stmt = $this->pdo->prepare('INSERT INTO categories (alias, title, rank, visible) VALUES (?, ?, ?, 0)');

		$stmt->bindParam(1, $alias);
		$stmt->bindParam(2, $title);
		$stmt->bindParam(3, $rank);

		$stmt->execute();

		/*---------------------------------------------------------*/
	}

	/*-----------------------------------------------------------------*/

	public function updateCategory()
	{
		if($this->isGuest())
		{
			$this->error('not authorized');
		}

		/*---------------------------------------------------------*/

		$id = $this->getParam('updateCategory');
		$alias = $this->getParam('categoryAlias');
		$title = $this->getParam('categoryTitle');
		$rank = $this->getParam('categoryRank');
		$visible = $this->getParam('categoryVisible', '0');

		if($id === '' || $alias === '' || $title === '' || $rank === '')
		{
			$this->error('missing parameter(s)');
		}

		/*---------------------------------------------------------*/

		$stmt = $this->pdo->prepare('UPDATE categories SET alias=?, title=?, rank=?, visible=? WHERE id=?');

		$stmt->bindParam(1, $alias);
		$stmt->bindParam(2, $title);
		$stmt->bindParam(3, $rank);
		$stmt->bindParam(4, $visible);
		$stmt->bindParam(5, $id);

		$stmt->execute();

		/*---------------------------------------------------------*/
	}

	/*-----------------------------------------------------------------*/

	public function delCategory()
	{
		if($this->isGuest())
		{
			$this->error('not authorized');
		}

		/*---------------------------------------------------------*/

		$id = $this->getParam('delCategory');

		/*---------------------------------------------------------*/

		$stmt = $this->pdo->prepare('DELETE FROM categories WHERE id=?');

		$stmt->bindParam(1, $id);

		$stmt->execute();

		/*---------------------------------------------------------*/
	}

	/*-----------------------------------------------------------------*/

	public function addPage()
	{
		if($this->isGuest())
		{
			$this->error('not authorized');
		}

		/*---------------------------------------------------------*/

		$alias = $this->getParam('pageAlias');
		$title = $this->getParam('pageTitle');

		if($alias === '' || $title === '')
		{
			$this->error('missing parameter(s)');
		}

		/*---------------------------------------------------------*/

		$stmt = $this->pdo->prepare('INSERT INTO pages (alias, title, visible) VALUES (?, ?, 0)');

		$stmt->bindParam(1, $alias);
		$stmt->bindParam(2, $title);

		$stmt->execute();

		/*---------------------------------------------------------*/
	}

	/*-----------------------------------------------------------------*/

	public function updatePage()
	{
		if($this->isGuest())
		{
			$this->error('not authorized');
		}

		/*---------------------------------------------------------*/

		$id = $this->getParam('updatePage');
		$alias = $this->getParam('pageAlias');
		$title = $this->getParam('pageTitle');
		$content = $this->getParam('pageContent');
		$visible = $this->getParam('pageVisible', '0');

		if($id === '' || $alias === '' || $title === '')
		{
			$this->error('missing parameter(s)');
		}

		/*---------------------------------------------------------*/

		$stmt = $this->pdo->prepare('UPDATE pages SET alias=?, title=?, content=?, visible=? WHERE id=?');

		$stmt->bindParam(1, $alias);
		$stmt->bindParam(2, $title);
		$stmt->bindParam(3, $content);
		$stmt->bindParam(4, $visible);
		$stmt->bindParam(5, $id);

		$stmt->execute();

		/*---------------------------------------------------------*/
	}

	/*-----------------------------------------------------------------*/

	public function delPage()
	{
		if($this->isGuest())
		{
			$this->error('not authorized');
		}

		/*---------------------------------------------------------*/

		$id = $this->getParam('delPage');

		/*---------------------------------------------------------*/

		$stmt = $this->pdo->prepare('DELETE FROM pages WHERE id=?');

		$stmt->bindParam(1, $id);

		$stmt->execute();

		/*---------------------------------------------------------*/
	}

	/*-----------------------------------------------------------------*/

	public function addArticle()
	{
		if($this->isGuest())
		{
			$this->error('not authorized');
		}

		/*---------------------------------------------------------*/

		$alias = $this->getParam('articleAlias');
		$category = $this->getParam('articleCategory');
		$title = $this->getParam('articleTitle');

		if($alias === '' || $category === '' || $title === '')
		{
			$this->error('missing parameter(s)');
		}

		/*---------------------------------------------------------*/

		$stmt = $this->pdo->prepare('INSERT INTO articles (alias, category, title, visible) VALUES (?, ?, ?, 0)');

		$stmt->bindParam(1, $alias);
		$stmt->bindParam(2, $category);
		$stmt->bindParam(3, $title);

		$stmt->execute();

		/*---------------------------------------------------------*/
	}

	/*-----------------------------------------------------------------*/

	public function updateArticle()
	{
		if($this->isGuest())
		{
			$this->error('not authorized');
		}

		/*---------------------------------------------------------*/

		$id = $this->getParam('updateArticle');
		$alias = $this->getParam('articleAlias');
		$category = $this->getParam('articleCategory');
		$title = $this->getParam('articleTitle');
		$content = $this->getParam('articleContent');
		$visible = $this->getParam('articleVisible', '0');

		if($id === '' || $alias === '' || $category === '' || $title === '')
		{
			$this->error('missing parameter(s)');
		}

		/*---------------------------------------------------------*/

		$stmt = $this->pdo->prepare('UPDATE articles SET alias=?, category=?, title=?, content=?, visible=? WHERE id=?');

		$stmt->bindParam(1, $alias);
		$stmt->bindParam(2, $category);
		$stmt->bindParam(3, $title);
		$stmt->bindParam(4, $content);
		$stmt->bindParam(5, $visible);
		$stmt->bindParam(6, $id);

		$stmt->execute();

		/*---------------------------------------------------------*/
	}

	/*-----------------------------------------------------------------*/

	public function delArticle()
	{
		if($this->isGuest())
		{
			$this->error('not authorized');
		}

		/*---------------------------------------------------------*/

		$id = $this->getParam('delArticle');

		/*---------------------------------------------------------*/

		$stmt = $this->pdo->prepare('DELETE FROM articles WHERE id=?');

		$stmt->bindParam(1, $id);

		$stmt->execute();

		/*---------------------------------------------------------*/
	}

	/*-----------------------------------------------------------------*/

	public function addMenu()
	{
		if($this->isGuest())
		{
			$this->error('not authorized');
		}

		/*---------------------------------------------------------*/

		$alias = $this->getParam('menuAlias');
		$category = $this->getParam('menuCategory');
		$parent = $this->getParam('menuParent');
		$title = $this->getParam('menuTitle');
		$rank = $this->getParam('menuRank');
		$page = $this->getParam('menuPage');
		$link = $this->getParam('menuLink');

		if($alias === '' || $category === '' || $title === '' || $rank === '' || $page === '')
		{
			$this->error('missing parameter(s)');
		}

		/*---------------------------------------------------------*/

		if($link === '')
		{
			if($parent === '')
			{
				$stmt = $this->pdo->prepare('INSERT INTO menus (alias, category, parent, title, rank, page, link) VALUES (?, ?, NULL, ?, ?, ?, NULL)');

				$stmt->bindParam(1, $alias);
				$stmt->bindParam(2, $category);
				$stmt->bindParam(3, $title);
				$stmt->bindParam(4, $rank);
				$stmt->bindParam(5, $page);
			}
			else
			{
				$stmt = $this->pdo->prepare('INSERT INTO menus (alias, category, parent, title, rank, page, link) VALUES (?, ?, ?, ?, ?, ?, NULL)');

				$stmt->bindParam(1, $alias);
				$stmt->bindParam(2, $category);
				$stmt->bindParam(3, $parent);
				$stmt->bindParam(4, $title);
				$stmt->bindParam(5, $rank);
				$stmt->bindParam(6, $page);
			}
		}
		else
		{
			if($parent === '')
			{
				$stmt = $this->pdo->prepare('INSERT INTO menus (alias, category, parent, title, rank, page, link) VALUES (?, ?, NULL, ?, ?, ?, ?)');

				$stmt->bindParam(1, $alias);
				$stmt->bindParam(2, $category);
				$stmt->bindParam(3, $title);
				$stmt->bindParam(4, $rank);
				$stmt->bindParam(5, $page);
				$stmt->bindParam(6, $link);
			}
			else
			{
				$stmt = $this->pdo->prepare('INSERT INTO menus (alias, category, parent, title, rank, page, link) VALUES (?, ?, ?, ?, ?, ?, ?)');

				$stmt->bindParam(1, $alias);
				$stmt->bindParam(2, $category);
				$stmt->bindParam(3, $parent);
				$stmt->bindParam(4, $title);
				$stmt->bindParam(5, $rank);
				$stmt->bindParam(6, $page);
				$stmt->bindParam(7, $link);
			}
		}

		$stmt->execute();

		/*---------------------------------------------------------*/
	}

	/*-----------------------------------------------------------------*/

	public function updateMenu()
	{
		if($this->isGuest())
		{
			$this->error('not authorized');
		}

		/*---------------------------------------------------------*/

		$id = $this->getParam('updateMenu');
		$alias = $this->getParam('menuAlias');
		$category = $this->getParam('menuCategory');
		$parent = $this->getParam('menuParent');
		$title = $this->getParam('menuTitle');
		$rank = $this->getParam('menuRank');
		$page = $this->getParam('menuPage');
		$link = $this->getParam('menuLink');
		$visible = $this->getParam('menuVisible', '0');

		if($id === '' || $alias === '' || $category === '' || $title === '' || $rank === '' || ($page === '' && $link === ''))
		{
			$this->error('missing parameter(s)');
		}

		/*---------------------------------------------------------*/

		if($link === '')
		{
			if($parent === '')
			{
				$stmt = $this->pdo->prepare('UPDATE menus SET alias=?, category=?, parent=NULL, title=?, rank=?, page=?, link=NULL, visible=? WHERE id=?');

				$stmt->bindParam(1, $alias);
				$stmt->bindParam(2, $category);
				$stmt->bindParam(3, $title);
				$stmt->bindParam(4, $rank);
				$stmt->bindParam(5, $page);
				$stmt->bindParam(6, $visible);
				$stmt->bindParam(7, $id);
			}
			else
			{
				$stmt = $this->pdo->prepare('UPDATE menus SET alias=?, category=?, parent=?, title=?, rank=?, page=?, link=NULL, visible=? WHERE id=?');

				$stmt->bindParam(1, $alias);
				$stmt->bindParam(2, $category);
				$stmt->bindParam(3, $parent);
				$stmt->bindParam(4, $title);
				$stmt->bindParam(5, $rank);
				$stmt->bindParam(6, $page);
				$stmt->bindParam(7, $visible);
				$stmt->bindParam(8, $id);
			}
		}
		else
		{
			if($parent === '')
			{
				$stmt = $this->pdo->prepare('UPDATE menus SET alias=?, category=?, parent=NULL, title=?, rank=?, page=?, link=?, visible=? WHERE id=?');

				$stmt->bindParam(1, $alias);
				$stmt->bindParam(2, $category);
				$stmt->bindParam(3, $title);
				$stmt->bindParam(4, $rank);
				$stmt->bindParam(5, $page);
				$stmt->bindParam(6, $link);
				$stmt->bindParam(7, $visible);
				$stmt->bindParam(8, $id);
			}
			else
			{
				$stmt = $this->pdo->prepare('UPDATE menus SET alias=?, category=?, parent=?, title=?, rank=?, page=?, link=?, visible=? WHERE id=?');

				$stmt->bindParam(1, $alias);
				$stmt->bindParam(2, $category);
				$stmt->bindParam(3, $parent);
				$stmt->bindParam(4, $title);
				$stmt->bindParam(5, $rank);
				$stmt->bindParam(6, $page);
				$stmt->bindParam(7, $link);
				$stmt->bindParam(8, $visible);
				$stmt->bindParam(9, $id);
			}
		}

		$stmt->execute();

		/*---------------------------------------------------------*/
	}

	/*-----------------------------------------------------------------*/

	public function delMenu()
	{
		if($this->isGuest())
		{
			$this->error('not authorized');
		}

		/*---------------------------------------------------------*/

		$id = $this->getParam('delMenu');

		/*---------------------------------------------------------*/

		$stmt = $this->pdo->prepare('DELETE FROM menus WHERE id=?');

		$stmt->bindParam(1, $id);

		$stmt->execute();

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
					$this->error('could not create thumbnail image: ' . $e->getMessage());
				}
			}
		}
		else
		{
			$this->error('could not create thumbnail image: extension `imagick` not installed');
		}
	}

	/*-----------------------------------------------------------------*/

	public function addFile()
	{
		if($this->isGuest())
		{
			$this->error('not authorized');
		}

		/*---------------------------------------------------------*/

		if(!isset($_FILES['files']) || !$_FILES['files'] || !isset($_FILES['files']['tmp_name']) || !isset($_FILES['files']['name']))
		{
			$this->error('missing parameter(s)');
		}

		/*---------------------------------------------------------*/

		$fromArray = $_FILES['files']['tmp_name'];
		$toArray = $_FILES['files']['name'];

		$nr = max(
			count($fromArray)
			,
			count($toArray)
		);

		for($i = 0; $i < $nr; $i++)
		{
			$from = $fromArray[$i];
			$to = $toArray[$i];

			if(copy("$from", "../media/$to"))
			{
				$this->compressImage("../media/$to");
			}
			else
			{
				$this->error("could not copy `$from` to `../media/$to`");
			}
		}

		/*---------------------------------------------------------*/
	}

	/*-----------------------------------------------------------------*/

	public function renFile()
	{
		if($this->isGuest())
		{
			$this->error('not authorized');
		}

		/*---------------------------------------------------------*/

		$oldFile = $this->getParam('oldFile');
		$newFile = $this->getParam('newFile');

		if($oldFile === '' && $newFile === '')
		{
			$this->error('missing parameter(s)');
		}

		/*---------------------------------------------------------*/

		if(rename("../media/$oldFile", "../media/$newFile") === false)
		{
			$this->error("could not rename `$oldFile` to `$newFile`");
		}

		/*---------------------------------------------------------*/
	}

	/*-----------------------------------------------------------------*/

	public function delFile()
	{
		if($this->isGuest())
		{
			$this->error('not authorized');
		}

		/*---------------------------------------------------------*/

		$file = $this->getParam('file');

		if($file === '')
		{
			$this->error('missing parameter(s)');
		}

		/*---------------------------------------------------------*/

		if(unlink("../media/$file") === false)
		{
			$this->error("could not delete `$file`");
		}

		/*---------------------------------------------------------*/
	}

	/*-----------------------------------------------------------------*/
	/*-----------------------------------------------------------------*/

	private function _buildWhereClause($opts)
	{
		$L = [];

		foreach($opts as $key => $val)
		{
			array_push($L, $key . '=\'' . str_replace('\'', '\'\'', $val) . '\'');
		}

		return count($L) > 0 ? ' WHERE ' . join($L, ' AND ') : '';
	}

	/*-----------------------------------------------------------------*/

	public function getCategories($opts = [])
	{
		/*---------------------------------------------------------*/

		$stmt = $this->pdo->prepare('SELECT * FROM categories' . $this->_buildWhereClause($opts) . ' ORDER BY rank, alias ASC');

		$stmt->execute();

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

		$stmt = $this->pdo->prepare('SELECT * FROM pages' . $this->_buildWhereClause($opts) . ' ORDER BY alias ASC');

		$stmt->execute();

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

		$stmt = $this->pdo->prepare('SELECT * FROM articlesV' . $this->_buildWhereClause($opts) . ' ORDER BY category, alias ASC');

		$stmt->execute();

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

		$stmt = $this->pdo->prepare('SELECT * FROM menusV' . $this->_buildWhereClause($opts) . ' ORDER BY category, parent, rank, alias ASC');

		$stmt->execute();

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

		$stmt = $this->pdo->prepare('SELECT * FROM categories WHERE id=?');

		$stmt->bindParam(1, $id);

		$stmt->execute();

		/*---------------------------------------------------------*/

		while($category = $stmt->fetch())
		{
			$stmt->closeCursor();

			die(json_encode($category));
		}

		/*---------------------------------------------------------*/

		$stmt->closeCursor();

		/*---------------------------------------------------------*/

		die('{}');

		/*---------------------------------------------------------*/
	}

	/*-----------------------------------------------------------------*/

	public function getPageJson()
	{
		/*---------------------------------------------------------*/

		$id = $this->getParam('getPageJson');

		/*---------------------------------------------------------*/

		$stmt = $this->pdo->prepare('SELECT * FROM pages WHERE id=?');

		$stmt->bindParam(1, $id);

		$stmt->execute();

		/*---------------------------------------------------------*/

		while($page = $stmt->fetch())
		{
			$stmt->closeCursor();

			die(json_encode($page));
		}

		/*---------------------------------------------------------*/

		$stmt->closeCursor();

		/*---------------------------------------------------------*/

		die('{}');

		/*---------------------------------------------------------*/
	}

	/*-----------------------------------------------------------------*/

	public function getArticleJson()
	{
		/*---------------------------------------------------------*/

		$id = $this->getParam('getArticleJson');

		/*---------------------------------------------------------*/

		$stmt = $this->pdo->prepare('SELECT * FROM articles WHERE id=?');

		$stmt->bindParam(1, $id);

		$stmt->execute();

		/*---------------------------------------------------------*/

		while($articles = $stmt->fetch())
		{
			$stmt->closeCursor();

			die(json_encode($articles));
		}

		/*---------------------------------------------------------*/

		$stmt->closeCursor();

		/*---------------------------------------------------------*/

		die('{}');

		/*---------------------------------------------------------*/
	}

	/*-----------------------------------------------------------------*/

	public function getMenuJson()
	{
		/*---------------------------------------------------------*/

		$id = $this->getParam('getMenuJson');

		/*---------------------------------------------------------*/

		$stmt = $this->pdo->prepare('SELECT * FROM menus WHERE id=?');

		$stmt->bindParam(1, $id);

		$stmt->execute();

		/*---------------------------------------------------------*/

		while($menu = $stmt->fetch())
		{
			$stmt->closeCursor();

			die(json_encode($menu));
		}

		/*---------------------------------------------------------*/

		$stmt->closeCursor();

		/*---------------------------------------------------------*/

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
			$Q1 = $this->getParam('q');
			$Q2 = $this->escapeHTML($q);
			$Q3 = "%{$Q1}%";

			/*-------------------------------------------------*/

			$stmt = $this->pdo->prepare('SELECT alias, title FROM pages WHERE (alias=? OR title LIKE ? OR content LIKE ?) AND visible!=0');

			$stmt->bindParam(1, $Q1);
			$stmt->bindParam(2, $Q3);
			$stmt->bindParam(3, $Q3);

			$stmt->execute();

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

			$stmt->closeCursor();

			/*-------------------------------------------------*/

			$stmt = $this->pdo->prepare('SELECT alias, title FROM articles WHERE (alias=? OR title LIKE ? OR content LIKE ?) AND visible!=0');

			$stmt->bindParam(1, $Q1);
			$stmt->bindParam(2, $Q3);
			$stmt->bindParam(3, $Q3);

			$stmt->execute();

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

			$stmt->closeCursor();

			/*-------------------------------------------------*/

			return [
				'path' => '<li class="breadcrumb-item active">Search</li>',
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
				foreach($this->getCategories(['visible' => '1']) as $category)
				{
					$alias = $this->escapeHTML($category['alias']);
					$title = $this->escapeHTML($category['title']);

					$result = "$result<tr>";
					$result = "$result<td><a href=\"/categories/$alias\">$title</a></td>";
					$result = "$result</tr>";

					$i++;
				}

				return [
					'path' => '<li class="breadcrumb-item active">categories</li>',
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
					'path' => '<li class="breadcrumb-item active">categories</li>',
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
					'path' => '<li class="breadcrumb-item active">pages</li>',
					'title' => 'Pages',
					'content' => "<table class=\"table\"><thead><tr><td>Title</td></tr></thead><tbody>$result</tbody></table><blockquote>$i matching result(s)</blockquote>",
					'date' => '',
				];
			}
			else
			{
				$stmt = $this->pdo->prepare('SELECT id, alias, title, content, DATE_FORMAT(date,\'%m-%d-%Y\') AS date FROM pages WHERE (id=? OR alias=?) AND visible!=0');

				$stmt->bindParam(1, $pages);
				$stmt->bindParam(2, $pages);

				$stmt->execute();
	
				while($line = $stmt->fetch())
				{
					return [
						'path' => "<li class="breadcrumb-item"><a href=\"/pages\">pages</a></li><li class=\"breadcrumb-item active\">{$line['alias']}</li>",
						'title' => $line['title'],
						'content' => (new Extension())->text($line['content']),
						'date' => $line['date'],
					];
				}

				$stmt->closeCursor();
			}
		}

		/*---------------------------------------------------------*/
		/* ARTICLES                                                */
		/*---------------------------------------------------------*/

		else if($this->hasParam('articles'))
		{
			$articles = $this->getParam('articles');

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
				$stmt = $this->pdo->prepare('SELECT id, alias, category, title, content, DATE_FORMAT(date,\'%m-%d-%Y\') AS date FROM articlesV WHERE (id=? OR alias=?) AND visible!=0');

				$stmt->bindParam(1, $articles);
				$stmt->bindParam(2, $articles);

				$stmt->execute();

				while($line = $stmt->fetch())
				{
					return [
						'path' => "<li class="breadcrumb-item"><a href=\"/articles\">articles</a></li><li class="breadcrumb-item"><a href=\"/categories/{$line['category']}\">{$line['category']}</a></li><li class=\"breadcrumb-item active\">{$line['alias']}</li>",
						'title' => $line['title'],
						'content' => (new Extension())->text($line['content']),
						'date' => $line['date'],
					];
				}

				$stmt->closeCursor();
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
/* INSTANCE                                                                */
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

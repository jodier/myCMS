<?php
/*-------------------------------------------------------------------------*/

require_once('mycms.php');

/*-------------------------------------------------------------------------*/

if($cms->isGuest())
{
	header('HTTP/1.0 403 Forbidden');

	die("You are not allowed to access this file.");
}

/*-------------------------------------------------------------------------*/
?>
<!DOCTYPE html>

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr" lang="fr">
	<head>
		<meta charset="utf-8" />
		<meta http-equiv="X-UA-Compatible" content="IE=edge" />
		<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
		<meta name="robots" content="noindex, nofollow" />

		<title>Admin Dashboard</title>

		<!-- CSS -->

		<link type="text/css" rel="stylesheet" href="css/bootstrap.min.css" />
		<link type="text/css" rel="stylesheet" href="css/bootstrap-toggle.min.css" />
		<link type="text/css" rel="stylesheet" href="css/font-awesome.min.css" />
		<link type="text/css" rel="stylesheet" href="css/admin.css" />

		<!-- JS -->

		<!--[if lt IE 9]>
		<script type="text/javascript" src="js/html5shiv.min.js"></script>
		<script type="text/javascript" src="js/respond.min.js"></script>
		<![endif]-->
	</head>
	<body>

		<nav id="admin_navbar">
			<span class="pull-left xxxxxxxxx" style="color: #777; font-size: 20pt;">
				<i class="fa fa-tachometer"></i> Admin Dashboard
			</span>
			<span class="pull-right hidden-xs" style="color: #777; font-size: 12pt;">
				IP: <?= "{$_SERVER['REMOTE_ADDR']}\n" ?>
			</span>
		</nav>

		<div class="container-fluid">

			<div class="row">

				<nav class="col-md-3" id="admin_sidebar">

					<br />

					<div class="panel panel-default">
						<div class="panel-heading">
							<h4 class="panel-title">Admin Dashboard</h4>
						</div>
						<div class="list-group">
							<a href="admin.php" class="list-group-item"><i class="fa fa-home"></i> Home</a>
						</div>
						<div class="list-group">
							<a href="admin.php?mode=categories" class="list-group-item"><i class="fa fa-pencil"></i> Manage categories</a>
							<a href="admin.php?mode=pages" class="list-group-item"><i class="fa fa-pencil"></i> Manage pages</a>
							<a href="admin.php?mode=articles" class="list-group-item"><i class="fa fa-pencil"></i> Manage articles</a>
							<a href="admin.php?mode=menus" class="list-group-item"><i class="fa fa-list"></i> Menage menus</a>
							<a href="admin.php?mode=media" class="list-group-item"><i class="fa fa-globe"></i> Manage media</a>
						</div>
					</div>

					<div class="text-center hidden-xs" style="background-color: #0091BE;">
						<img src="images/html5.png" width="175" alt="HTML5 Powered with CSS3" />
					</div>

					Disk usage:
					<div class="progress" style="margin-bottom: 0px;">
						<div class="progress-bar progress-bar-info progress-bar-striped" role="progressbar" style="width: <?php printf("%.1f", $cms->getDiskUsage()); ?>%;"><?php printf("%.1f", $cms->getDiskUsage()); ?></div>
					</div>

					Mem usage:
					<div class="progress" style="margin-bottom: 0px;">
						<div class="progress-bar progress-bar-info progress-bar-striped" role="progressbar" style="width: <?php printf("%.1f", $cms->getMemUsage()); ?>%;"><?php printf("%.1f", $cms->getMemUsage()); ?></div>
					</div>

				</nav>

				<main class="col-md-9" id="admin_main">

					<br />

<?php

$errorInfo = $cms->errorInfo();

if($errorInfo !== '')
{
	print("\t\t\t\t\t<div class=\"alert alert-danger alert-dismissible\" role=\"alert\">\n");
	print("\t\t\t\t\t\t<button type=\"button\" class=\"close\" data-dismiss=\"alert\">×</button>\n");
	print("\t\t\t\t\t\t<i class=\"fa fa-exclamation-circle\"></i> $errorInfo\n");
	print("\t\t\t\t\t</div>\n");
	print("\n");
}

$mode = $cms->getParam('mode');

/*-------------------------------------------------------------------------*/
/* CATEGORIES SUBAPP                                                       */
/*-------------------------------------------------------------------------*/

/**/ if($mode === 'categories')
{
?>
					<span class="btn btn-default btn-sm" onclick="$('#categoryAdder').toggle(); $(this).toggleClass('active');"><i class="fa fa-plus"></i></span>

					<form class="callout" action="admin.php" method="POST" onsubmit="return _confirm();" id="categoryAdder">
						<div class="form-group form-group-sm">
							<label for="categoryAlias">Category alias</label>
							<input type="text" name="categoryAlias" class="form-control" id="categoryAlias" placeholder="Category alias" required="required" />
						</div>
						<div class="form-group form-group-sm">
							<label for="categoryTitle">Category title</label>
							<input type="text" name="categoryTitle" class="form-control" id="categoryTitle" placeholder="Category title" required="required" />
						</div>
						<div class="form-group form-group-sm">
							<label for="categoryRank">Category rank</label>
							<input type="number" name="categoryRank" class="form-control" id="categoryRank" placeholder="Category rank" required="required" />
						</div>
						<div class="text-right">
							<input type="hidden" name="mode" value="categories" />
							<button type="submit" name="addCategory" class="btn btn-default btn-sm">New category</button>
						</div>
					</form>

					<div class="form-group">
						<input type="text" class="form-control" placeholder="&#x1f50d; filter" id="category_filter" />
					</div>

					<table class="table table-striped" id="category_table">
						<thead>
							<tr>
								<th>Alias</th>
								<th>Title</th>
								<th>Rank</th>
								<th>Visible</th>
								<th></th>
								<th></th>
							</tr>
						</thead>
						<tbody>
<?php
foreach($cms->getCategories() as $key => $val)
{
	$id = $cms->escapeHTML($val['id']);
	$alias = $cms->escapeHTML($val['alias']);
	$title = $cms->escapeHTML($val['title']);
	$rank = $cms->escapeHTML($val['rank']);
	$visible = $cms->escapeHTML($val['visible']);

	print("\t\t\t\t\t\t\t<tr>\n");
	print("\t\t\t\t\t\t\t\t<td><a href=\"/categories/$alias\" target=\"_blank\">$alias</a></td>\n");
	print("\t\t\t\t\t\t\t\t<td>$title</td>\n");
	print("\t\t\t\t\t\t\t\t<td>$rank</td>\n");
	print("\t\t\t\t\t\t\t\t<td>$visible</td>\n");
	print("\t\t\t\t\t\t\t\t<td><a href=\"javascript:editCategory($id);\"><i class=\"fa fa-pencil text-primary\"></i></a></td>\n");
	print("\t\t\t\t\t\t\t\t<td><a href=\"javascript:delCategory($id);\"><i class=\"fa fa-trash text-danger\"></i></a></td>\n");
	print("\t\t\t\t\t\t\t</tr>\n");
}
?>
						</tbody>
					</table>

					<form class="modal" id="categoryModal">
						<div class="modal-dialog">
							<div class="modal-content">
								<div class="modal-header">
									<button type="button" class="close" data-dismiss="modal">&times;</button>
									<h4 class="modal-title">Edit category <input type="checkbox" name="categoryVisible" value="1" id="categoryVisibleInModal" data-toggle="toggle" data-width="80" data-on="Visible" data-off="Hidden" /></h4>
								</div>
								<div class="modal-body">
									<div class="form-group form-group-sm">
										<label for="categoryAliasInModal">Category alias</label>
										<input type="text" name="categoryAlias" class="form-control" id="categoryAliasInModal" placeholder="Category alias" required="required" />
									</div>
									<div class="form-group form-group-sm">
										<label for="categoryTitleInModal">Category title</label>
										<input type="text" name="categoryTitle" class="form-control" id="categoryTitleInModal" placeholder="Category title" required="required" />
									</div>
									<div class="form-group form-group-sm">
										<label for="categoryRankInModal">Category rank</label>
										<input type="number" name="categoryRank" class="form-control" id="categoryRankInModal" placeholder="Category rank" required="required" />
									</div>
								</div>
								<div class="modal-footer">
									<input type="hidden" name="mode" value="categories" />
									<input type="hidden" name="updateCategory" id="categoryIdInModal" />
									<button type="button" class="btn btn-default btn-sm" onclick="formUpdateCategory(false);">Apply</button>
									<button type="button" class="btn btn-default btn-sm" onclick="formUpdateCategory(true);">Apply and close</button>
								</div>
							</div>
						</div>
					</form>
<?php
}

/*-------------------------------------------------------------------------*/
/* PAGES SUBAPP                                                            */
/*-------------------------------------------------------------------------*/

else if($mode === 'pages')
{
?>
					<span class="btn btn-default btn-sm" onclick="$('#pageAdder').toggle(); $(this).toggleClass('active');"><i class="fa fa-plus"></i></span>

					<form class="callout" action="admin.php" method="POST" onsubmit="return _confirm();" id="pageAdder">
						<div class="form-group form-group-sm">
							<label for="pageAlias">Page alias</label>
							<input type="text" name="pageAlias" class="form-control" id="pageAlias" placeholder="Page alias" required="required" />
						</div>
						<div class="form-group form-group-sm">
							<label for="pageTitle">Page title</label>
							<input type="text" name="pageTitle" class="form-control" id="pageTitle" placeholder="Page title" required="required" />
						</div>
						<div class="text-right">
							<input type="hidden" name="mode" value="pages" />
							<button type="submit" name="addPage" class="btn btn-default btn-sm">New page</button>
						</div>
					</form>

					<div class="form-group">
						<input type="text" class="form-control" placeholder="&#x1f50d; filter" id="page_filter" />
					</div>

					<table class="table table-striped" id="page_table">
						<thead>
							<tr>
								<th>Alias</th>
								<th>Title</th>
								<th>Visible</th>
								<th></th>
								<th></th>
							</tr>
						</thead>
						<tbody>
<?php
foreach($cms->getPages() as $key => $val)
{
	$id = $cms->escapeHTML($val['id']);
	$alias = $cms->escapeHTML($val['alias']);
	$title = $cms->escapeHTML($val['title']);
	$visible = $cms->escapeHTML($val['visible']);

	print("\t\t\t\t\t\t\t<tr>\n");
	print("\t\t\t\t\t\t\t\t<td><a href=\"/pages/$alias\" target=\"_blank\">$alias</a></td>\n");
	print("\t\t\t\t\t\t\t\t<td>$title</td>\n");
	print("\t\t\t\t\t\t\t\t<td>$visible</td>\n");
	print("\t\t\t\t\t\t\t\t<td><a href=\"javascript:editPage($id);\"><i class=\"fa fa-pencil text-primary\"></i></a></td>\n");
	print("\t\t\t\t\t\t\t\t<td><a href=\"javascript:delPage($id);\"><i class=\"fa fa-trash text-danger\"></i></a></td>\n");
	print("\t\t\t\t\t\t\t</tr>\n");
}
?>
						</tbody>
					</table>

					<form class="modal" id="pageModal">
						<div class="modal-dialog modal-lg">
							<div class="modal-content">
								<div class="modal-header">
									<button type="button" class="close" data-dismiss="modal">&times;</button>
									<h4 class="modal-title">Edit page <input type="checkbox" name="pageVisible" value="1" id="pageVisibleInModal" data-toggle="toggle" data-width="80" data-on="Visible" data-off="Hidden" /></h4>
								</div>
								<div class="modal-body form-horizontal">
									<div class="form-group form-group-sm">
										<label for="pageAliasInModal" class="col-sm-2">Alias</label>
										<div class="col-sm-10">
											<input type="text" name="pageAlias" class="form-control" id="pageAliasInModal" placeholder="Page alias" required="required" />
										</div>
									</div>
									<div class="form-group form-group-sm">
										<label for="pageTitleInModal" class="col-sm-2">Title</label>
										<div class="col-sm-10">
											<input type="text" name="pageTitle" class="form-control" id="pageTitleInModal" placeholder="Page title" required="required" />
										</div>
									</div>
									<textarea name="pageContent" class="form-control markdown" id="pageContentInModal" style="width: 100%; height: 325px;"></textarea>
								</div>
								<div class="modal-footer">
									<input type="hidden" name="mode" value="pages" />
									<input type="hidden" name="updatePage" id="pageIdInModal" />
									<a href="https://help.github.com/articles/github-flavored-markdown/" target="_blank" class="btn btn-link">help</a>
									<button type="button" class="btn btn-default btn-sm" onclick="formUpdatePage(false);">Apply</button>
									<button type="button" class="btn btn-default btn-sm" onclick="formUpdatePage(true);">Apply and close</button>
								</div>
							</div>
						</div>
					</form>
<?php
}

/*-------------------------------------------------------------------------*/
/* ARTICLES SUBAPP                                                         */
/*-------------------------------------------------------------------------*/

else if($mode === 'articles')
{
?>
					<span class="btn btn-default btn-sm" onclick="$('#articleAdder').toggle(); $(this).toggleClass('active');"><i class="fa fa-plus"></i></span>

					<form class="callout" action="admin.php" method="POST" onsubmit="return _confirm();" id="articleAdder">
						<div class="form-group form-group-sm">
							<label for="articleAlias">Article alias</label>
							<input type="text" name="articleAlias" class="form-control" id="articleAlias" placeholder="Article alias" required="required" />
						</div>
						<div class="form-group form-group-sm">
							<label for="articleCategory">Article category</label>
							<select name="articleCategory" class="form-control" id="articleCategory">
<?php foreach($cms->getCategories() as $key => $val) print("\t\t\t\t\t\t\t\t<option value=\"{$val['id']}\">{$val['alias']}</option>\n"); ?>
							</select>
						</div>
						<div class="form-group form-group-sm">
							<label for="articleTitle">Article title</label>
							<input type="text" name="articleTitle" class="form-control" id="articleTitle" placeholder="Article title" required="required" />
						</div>
						<div class="text-right">
							<input type="hidden" name="mode" value="articles" />
							<button type="submit" name="addArticle" class="btn btn-default btn-sm">New article</button>
						</div>
					</form>

					<div class="form-group">
						<input type="text" class="form-control" placeholder="&#x1f50d; filter" id="article_filter" />
					</div>

					<table class="table table-striped" id="article_table">
						<thead>
							<tr>
								<th>Alias</th>
								<th>Category</th>
								<th>Title</th>
								<th>Visible</th>
								<th></th>
								<th></th>
							</tr>
						</thead>
						<tbody>
<?php
foreach($cms->getArticles() as $key => $val)
{
	$id = $cms->escapeHTML($val['id']);
	$alias = $cms->escapeHTML($val['alias']);
	$category = $cms->escapeHTML($val['category']);
	$title = $cms->escapeHTML($val['title']);
	$visible = $cms->escapeHTML($val['visible']);

	print("\t\t\t\t\t\t\t<tr>\n");
	print("\t\t\t\t\t\t\t\t<td><a href=\"/articles/$alias\" target=\"_blank\">$alias</a></td>\n");
	print("\t\t\t\t\t\t\t\t<td><a href=\"/categories/$category\" target=\"_blank\">$category</a></td>\n");
	print("\t\t\t\t\t\t\t\t<td>$title</td>\n");
	print("\t\t\t\t\t\t\t\t<td>$visible</td>\n");
	print("\t\t\t\t\t\t\t\t<td><a href=\"javascript:editArticle($id);\"><i class=\"fa fa-pencil text-primary\"></i></a></td>\n");
	print("\t\t\t\t\t\t\t\t<td><a href=\"javascript:delArticle($id);\"><i class=\"fa fa-trash text-danger\"></i></a></td>\n");
	print("\t\t\t\t\t\t\t</tr>\n");
}
?>
						</tbody>
					</table>

					<form class="modal" id="articleModal">
						<div class="modal-dialog modal-lg">
							<div class="modal-content">
								<div class="modal-header">
									<button type="button" class="close" data-dismiss="modal">&times;</button>
									<h4 class="modal-title">Edit article <input type="checkbox" name="articleVisible" value="1" id="articleVisibleInModal" data-toggle="toggle" data-width="80" data-on="Visible" data-off="Hidden" /></h4>
								</div>
								<div class="modal-body form-horizontal">
									<div class="form-group form-group-sm">
										<label for="articleAliasInModal" class="col-sm-2">Alias</label>
										<div class="col-sm-10">
											<input type="text" name="articleAlias" class="form-control" id="articleAliasInModal" placeholder="Article alias" required="required" />
										</div>
									</div>
									<div class="form-group form-group-sm">
										<label for="articleCategoryInModal" class="col-sm-2">Category</label>
										<div class="col-sm-10">
											<select name="articleCategory" class="form-control" id="articleCategoryInModal">
<?php foreach($cms->getCategories() as $key => $val) print("\t\t\t\t\t\t\t\t\t\t\t\t<option value=\"{$val['id']}\">{$val['alias']}</option>\n"); ?>
											</select>
										</div>
									</div>
									<div class="form-group form-group-sm">
										<label for="articleTitleInModal" class="col-sm-2">Title</label>
										<div class="col-sm-10">
											<input type="text" name="articleTitle" class="form-control" id="articleTitleInModal" placeholder="Article title" required="required" />
										</div>
									</div>
									<textarea name="articleContent" class="form-control markdown" id="articleContentInModal" style="width: 100%; height: 325px;"></textarea>
								</div>
								<div class="modal-footer">
									<input type="hidden" name="mode" value="articles" />
									<input type="hidden" name="updateArticle" id="articleIdInModal" />
									<a href="https://help.github.com/articles/github-flavored-markdown/" target="_blank" class="btn btn-link">help</a>
									<button type="button" class="btn btn-default btn-sm" onclick="formUpdateArticle(false);">Apply</button>
									<button type="button" class="btn btn-default btn-sm" onclick="formUpdateArticle(true);">Apply and close</button>
								</div>
							</div>
						</div>
					</form>
<?php
}

/*-------------------------------------------------------------------------*/
/* MENUS SUBAPP                                                            */
/*-------------------------------------------------------------------------*/

else if($mode === 'menus')
{
?>
					<span class="btn btn-default btn-sm" onclick="$('#menuAdder').toggle(); $(this).toggleClass('active');"><i class="fa fa-plus"></i></span>

					<form class="callout" action="admin.php" method="POST" onsubmit="return _confirm();" id="menuAdder">
						<div class="form-group form-group-sm">
							<label for="menuAlias">Menu alias</label>
							<input type="text" name="menuAlias" class="form-control" id="menuAlias" placeholder="Menu alias" required="required" />
						</div>
						<div class="form-group form-group-sm">
							<label for="menuCategory">Menu category</label>
							<select name="menuCategory" class="form-control" id="menuCategory">
<?php foreach($cms->getCategories() as $key => $val) print("\t\t\t\t\t\t\t\t<option value=\"{$val['id']}\">{$val['alias']}</option>\n"); ?>
							</select>
						</div>
						<div class="form-group form-group-sm">
							<label for="menuParent">Menu parent</label>
							<select name="menuParent" class="form-control" id="menuParent">
								<option value="">-- NONE --</option>
<?php foreach($cms->getMenus() as $key => $val) print("\t\t\t\t\t\t\t\t<option value=\"{$val['id']}\">{$val['alias']}</option>\n"); ?>
							</select>
						</div>
						<div class="form-group form-group-sm">
							<label for="menuTitle">Menu title</label>
							<input type="text" name="menuTitle" class="form-control" id="menuTitle" placeholder="Menu title" required="required" />
						</div>
						<div class="form-group form-group-sm">
							<label for="menuRank">Menu rank</label>
							<input type="number" name="menuRank" class="form-control" id="menuRank" placeholder="Menu rank" required="required" />
						</div>
						<table style="width: 100%;">
							<tr>
								<td>
									<div class="form-group form-group-sm">
										<label for="menuPage">Menu page (internal link)</label>
										<select name="menuPage" class="form-control" id="menuPage">
<?php foreach($cms->getPages() as $key => $val) print("\t\t\t\t\t\t\t\t<option value=\"{$val['id']}\">{$val['alias']}</option>\n"); ?>
										</select>
									</div>
								</td>
								<td class="text-center">&nbsp;or&nbsp;</td>
								<td>
									<div class="form-group form-group-sm">
										<label for="menuLink">Menu page (external link)</label>
										<input type="text" name="menuLink" class="form-control" id="menuLink" placeholder="Menu link" />
									</div>
								</td>
							</tr>
						</table>
						<div class="text-right">
							<input type="hidden" name="mode" value="menus" />
							<button type="submit" name="addMenu" class="btn btn-default btn-sm">New menu</button>
						</div>
					</form>

					<div class="form-group">
						<input type="text" class="form-control" placeholder="&#x1f50d; filter" id="menu_filter" />
					</div>

					<table class="table table-striped" id="menu_table">
						<thead>
							<tr>
								<th>Alias</th>
								<th>Category</th>
								<th>Parent</th>
								<th>Title</th>
								<th>Rank</th>
								<th>Page</th>
								<th>Visible</th>
								<th></th>
								<th></th>
							</tr>
						</thead>
						<tbody>
<?php
foreach($cms->getMenus() as $key => $val)
{
	$id = $cms->escapeHTML($val['id']);
	$alias = $cms->escapeHTML($val['alias']);
	$category = $cms->escapeHTML($val['category']);
	$parent = $cms->escapeHTML($val['parent']);
	$title = $cms->escapeHTML($val['title']);
	$rank = $cms->escapeHTML($val['rank']);
	$page = $cms->escapeHTML($val['page']);
	$visible = $cms->escapeHTML($val['visible']);

	print("\t\t\t\t\t\t\t<tr>\n");
	print("\t\t\t\t\t\t\t\t<td>$alias</td>\n");
	print("\t\t\t\t\t\t\t\t<td><a href=\"/categories/$category\" target=\"_blank\">$category</a></td>\n");
	print("\t\t\t\t\t\t\t\t<td>$parent</td>\n");
	print("\t\t\t\t\t\t\t\t<td>$title</td>\n");
	print("\t\t\t\t\t\t\t\t<td>$rank</td>\n");
	print("\t\t\t\t\t\t\t\t<td><a href=\"$page\" target=\"_blank\"><i class=\"fa fa-external-link\"></i></a></td>\n");
	print("\t\t\t\t\t\t\t\t<td>$visible</td>\n");
	print("\t\t\t\t\t\t\t\t<td><a href=\"javascript:editMenu($id);\"><i class=\"fa fa-pencil text-primary\"></i></a></td>\n");
	print("\t\t\t\t\t\t\t\t<td><a href=\"javascript:delMenu($id);\"><i class=\"fa fa-trash text-danger\"></i></a></td>\n");
	print("\t\t\t\t\t\t\t</tr>\n");
}
?>
						</tbody>
					</table>

					<form class="modal" id="menuModal">
						<div class="modal-dialog">
							<div class="modal-content">
								<div class="modal-header">
									<button type="button" class="close" data-dismiss="modal">&times;</button>
									<h4 class="modal-title">Edit menu <input type="checkbox" name="menuVisible" value="1" id="menuVisibleInModal" data-toggle="toggle" data-width="80" data-on="Visible" data-off="Hidden" /></h4>
								</div>
								<div class="modal-body form-horizontal">
									<div class="form-group form-group-sm">
										<label for="menuAliasInModal" class="col-sm-2">Alias</label>
										<div class="col-sm-10">
											<input type="text" name="menuAlias" class="form-control" id="menuAliasInModal" placeholder="Menu alias" required="required" />
										</div>
									</div>
									<div class="form-group form-group-sm">
										<label for="menuCategoryInModal" class="col-sm-2">Category</label>
										<div class="col-sm-10">
											<select name="menuCategory" class="form-control" id="menuCategoryInModal">
<?php foreach($cms->getCategories() as $key => $val) print("\t\t\t\t\t\t\t\t\t\t\t\t<option value=\"{$val['id']}\">{$val['alias']}</option>\n"); ?>
											</select>
										</div>
									</div>
									<div class="form-group form-group-sm">
										<label for="menuParentInModal" class="col-sm-2">Parent</label>
										<div class="col-sm-10">
											<select name="menuParent" class="form-control" id="menuParentInModal">
												<option value="">-- NONE --</option>
<?php foreach($cms->getMenus() as $key => $val) print("\t\t\t\t\t\t\t\t\t\t\t\t<option value=\"{$val['id']}\">{$val['alias']}</option>\n"); ?>
											</select>
										</div>
									</div>
									<div class="form-group form-group-sm">
										<label for="menuTitleInModal" class="col-sm-2">Title</label>
										<div class="col-sm-10">
											<input type="text" name="menuTitle" class="form-control" id="menuTitleInModal" placeholder="Menu title" required="required" />
										</div>
									</div>
									<div class="form-group form-group-sm">
										<label for="menuRankInModal" class="col-sm-2">Rank</label>
										<div class="col-sm-10">
											<input type="number" name="menuRank" class="form-control" id="menuRankInModal" placeholder="Menu rank" required="required" />
										</div>
									</div>
									<div class="form-group form-group-sm">
										<label for="menuPageInModal" class="col-sm-2">Link (int)</label>
										<div class="col-sm-10">
											<select name="menuPage" class="form-control" id="menuPageInModal">
<?php foreach($cms->getPages() as $key => $val) print("\t\t\t\t\t\t\t\t\t\t\t\t<option value=\"{$val['id']}\">{$val['alias']}</option>\n"); ?>
											</select>
										</div>
									</div>
									<div class="form-group form-group-sm">
										<label for="menuLinkInModal" class="col-sm-2">Link (ext)</label>
										<div class="col-sm-10">
											<input type="text" name="menuLink" class="form-control" id="menuLinkInModal" placeholder="Menu link" />
										</div>
									</div>
								</div>
								<div class="modal-footer">
									<input type="hidden" name="mode" value="menus" />
									<input type="hidden" name="updateMenu" id="menuIdInModal" />
									<a href="https://help.github.com/articles/github-flavored-markdown/" target="_blank" class="btn btn-link">help</a>
									<button type="button" class="btn btn-default btn-sm" onclick="formUpdateMenu(false);">Apply</button>
									<button type="button" class="btn btn-default btn-sm" onclick="formUpdateMenu(true);">Apply and close</button>
								</div>
							</div>
						</div>
					</form>
<?php
}

/*-------------------------------------------------------------------------*/
/* MEDIAS SUBAPP                                                           */
/*-------------------------------------------------------------------------*/

else if($mode === 'media')
{
?>
					<div class="progress">
						<div class="progress-bar progress-bar-info progress-bar-striped active" role="progressbar" id="progress_bar"></div>
					</div>

					<div id="drop_zone"><i class="fa fa-upload"></i><br />Drop files to add them</div>

					<div class="form-group">
						<input type="text" class="form-control" placeholder="&#x1f50d; filter" id="media_filter" />
					</div>

					<table class="table table-striped" id="media_table">
						<thead>
							<tr>
								<th>Name</th>
								<th>Size</th>
								<th>Date</th>
								<th></th>
								<th></th>
							</tr>
						</thead>
						<tbody>
<?php
/*-------------------------------------------------------------------------*/

function filesize_format($size, $sizes = array('Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'))
{
	$i = floor(log($size, 1024));

	return $size > 0 ? (round($size / pow(1024, $i), 2) . ' ' . $sizes[$i]) : 'n/a';
}

/*-------------------------------------------------------------------------*/

function filemtime_format($date)
{
	return date ('F d Y H:i:s', $date);
}

/*-------------------------------------------------------------------------*/

$names = [];

$dir = opendir('../media');

while($name = readdir($dir))
{
	if($name !== '.'
	   &&
	   $name !== '..'
	 ) {
	 	array_push($names, $name);
	 }
}

closedir($dir);

/*-------------------------------------------------------------------------*/

sort($names);

foreach($names AS $name)
{
	$size = filesize("../media/$name");
	$date = filemtime("../media/$name");

	$NAME = $cms->escapeHTML($name);
	$SIZE = filesize_format($size);
	$DATE = filemtime_format($date);

	print("\t\t\t\t\t\t\t<tr>\n");
	print("\t\t\t\t\t\t\t\t<td><a href=\"/media/$name\" target=\"_blank\">$NAME</a></td>\n");
	print("\t\t\t\t\t\t\t\t<td>$SIZE</td>\n");
	print("\t\t\t\t\t\t\t\t<td>$DATE</td>\n");
	print("\t\t\t\t\t\t\t\t<td><a href=\"javascript:renFile('$NAME')\"><i class=\"fa fa-pencil text-primary\"></i></a></td>\n");
	print("\t\t\t\t\t\t\t\t<td><a href=\"javascript:delFile('$NAME')\"><i class=\"fa fa-trash text-danger\"></i></a></td>\n");
	print("\t\t\t\t\t\t\t</tr>\n");
}

/*-------------------------------------------------------------------------*/
?>
						</tbody>
					</table>
<?php
}

/*-------------------------------------------------------------------------*/
/* HOME SUBAPP                                                             */
/*-------------------------------------------------------------------------*/

else
{
?>
					<div class="row">

						<div class="col-md-4">

							<div class="panel panel-primary">
								<div class="panel-heading">
									 <i class="fa fa-pencil fa-5x"></i>
								</div>
								<a href="admin.php?mode=categories">
									<div class="panel-footer">
										<span class="pull-left">Manage categories</span>
										<span class="pull-right"><i class="fa fa-chevron-right"></i></span>
										<div class="clearfix"></div>
									</div>
								</a>
							</div>

						</div>

						<div class="col-md-4">

							<div class="panel panel-primary">
								<div class="panel-heading">
									 <i class="fa fa-pencil fa-5x"></i>
								</div>
								<a href="admin.php?mode=pages">
									<div class="panel-footer">
										<span class="pull-left">Manage pages</span>
										<span class="pull-right"><i class="fa fa-chevron-right"></i></span>
										<div class="clearfix"></div>
									</div>
								</a>
							</div>

						</div>

						<div class="col-md-4">

							<div class="panel panel-primary">
								<div class="panel-heading">
									 <i class="fa fa-pencil fa-5x"></i>
								</div>
								<a href="admin.php?mode=articles">
									<div class="panel-footer">
										<span class="pull-left">Manage articles</span>
										<span class="pull-right"><i class="fa fa-chevron-right"></i></span>
										<div class="clearfix"></div>
									</div>
								</a>
							</div>

						</div>

					</div>

					<div class="row">

						<div class="col-md-4">

							<div class="panel panel-primary">
								<div class="panel-heading">
									 <i class="fa fa-list fa-5x"></i>
								</div>
								<a href="admin.php?mode=menus">
									<div class="panel-footer">
										<span class="pull-left">Manage menus</span>
										<span class="pull-right"><i class="fa fa-chevron-right"></i></span>
										<div class="clearfix"></div>
									</div>
								</a>
							</div>

						</div>

						<div class="col-md-4">

							<div class="panel panel-success">
								<div class="panel-heading">
									 <i class="fa fa-picture-o fa-5x"></i>
								</div>
								<a href="admin.php?mode=media">
									<div class="panel-footer">
										<span class="pull-left">Manage media</span>
										<span class="pull-right"><i class="fa fa-chevron-right"></i></span>
										<div class="clearfix"></div>
									</div>
								</a>
							</div>

						</div>

						<div class="col-md-4">

							<div class="panel panel-default">
								<div class="panel-heading">
									 <i class="fa fa-globe fa-5x"></i>
								</div>
								<a href="/" target="_blank">
									<div class="panel-footer">
										<span class="pull-left">Web site</span>
										<span class="pull-right"><i class="glyphicon glyphicon-link"></i></span>
										<div class="clearfix"></div>
									</div>
								</a>
							</div>

						</div>

					</div>

					<div class="row">
						<div class="col-md-6">
							<p><i class="fa fa-chevron-right"></i> <a href="admin.php?setupDB">Setup DB</a></p>

							<p><i class="fa fa-chevron-right"></i> <a href="admin.php?upgradeCMS">Upgrade CMS</a></p>

							<p><i class="fa fa-chevron-right"></i> <a href="http://www.odier.eu/" target="_blank">By Jérôme ODIER</a></p>
						</div>
						<div class="col-md-6">
							<p><i class="fa fa-chevron-right"></i> Force HTTPS: <input type="checkbox" data-toggle="toggle" data-width="80" /></p>

							<p><i class="fa fa-chevron-right"></i> Description: <br /><input type="text" class="form-control" placeholder="Description" /></p>

							<p><i class="fa fa-chevron-right"></i> Author: <br /><input type="text" class="form-control" placeholder="Author" /></p>
						</div>
					</div>

					<div class="text-right"><button type="button" class="btn btn-default">Apply</button></div>
<?php
}

/*-------------------------------------------------------------------------*/
?>

				</main>

			</div>

		</div>

		<script type="text/javascript" src="js/jquery.min.js"></script>
		<script type="text/javascript" src="js/bootstrap.min.js"></script>
		<script type="text/javascript" src="js/bootstrap-toggle.min.js"></script>
		<script type="text/javascript" src="js/marked.min.js"></script>
		<script type="text/javascript" src="js/admin.js"></script>

	</body>
</html>

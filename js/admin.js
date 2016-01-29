/*-------------------------------------------------------------------------*/
/* DASHBOARD                                                               */
/*-------------------------------------------------------------------------*/

$(window).on('resize', function() {

	$('#admin_main').css('min-height', $(window).height() - $('#admin_main').position().top);
});

$('#admin_main').css('min-height', $(window).height() - $('#admin_main').position().top);

/*-------------------------------------------------------------------------*/
/* EDITOR                                                                  */
/*-------------------------------------------------------------------------*/

function _showViewer(target)
{
	var editor = $(target).find('textarea.editor');
	var viewer = $(target).find('div.viewer'     );

	if(editor.width() === viewer.width())
	{
		editor.css('width', '100%');
		viewer.css('width', '000%');
		viewer.hide();
	}
	else
	{
		editor.css('width', '50%');
		viewer.css('width', '50%');
		viewer.show();
	}
}

/*-------------------------------------------------------------------------*/

function _goFullScreen(target)
{
	if(window.innerWidth !== screen.width
	   ||
	   window.innerHeight !== screen.height
	 ) {
		/**/ if(target.webkitRequestFullScreen) {
			target.webkitRequestFullScreen();
		}
		else if(target.mozRequestFullScreen) {
			target.mozRequestFullScreen();
		}
		else if(target.msRequestFullScreen) {
			target.msRequestFullScreen();
		}
		else if(target.requestFullscreen) {
			target.requestFullscreen();
		}
	}
	else
	{
		/**/ if(document.webkitCancelFullScreen) {
			document.webkitCancelFullScreen();
        	}
		else if(document.mozCancelFullScreen) {
			document.mozCancelFullScreen();
		}
		else if(document.msCancelFullScreen) {
			document.msCancelFullScreen();
		}
		else if(document.exitFullscreen) {
			document.exitFullscreen();
		}
	}
}

/*-------------------------------------------------------------------------*/

$(document).on('webkitfullscreenchange mozfullscreenchange msfullscreenchange fullscreenchange', function(e) {

	if(window.innerWidth !== screen.width
	   ||
	   window.innerHeight !== screen.height
	 ) {
		e.target.style.height = '325px';
	}
	else
	{
		e.target.style.height = screen.height + 'px';
	}
});

/*-------------------------------------------------------------------------*/

var markdownRenderer = new marked.Renderer();

markdownRenderer.table = function(header, body)
{
	return '<table class="table table-striped">\n<thead>\n' + header + '</thead>\n<tbody>\n' + body + '</tbody>\n</table>\n';
};

/*-------------------------------------------------------------------------*/

var markdownOptions = {
	gfm: true,
	tables: true,
	breaks: false,
	pedantic: false,
	sanitize: false,
	smartLists: true,
	smartypants: false,
	renderer: markdownRenderer,
};

/*-------------------------------------------------------------------------*/

$('.markdown').replaceWith(function() {

	return '<div class="markdown-editor">'
	       +
	       '  <textarea class="editor" name="' + $(this).attr('name') + '" id="' + $(this).attr('id') + '"></textarea>'
	       +
	       '  <div class="viewer"></div>'
	       +
	       '  <div class="btn-group-vertical">'
	       +
	       '    <button type="button" class="btn btn-default" onclick="_showViewer(this.parentNode.parentNode);"><i class="fa fa-arrows-h"></i></button>'
	       +
	       '    <button type="button" class="btn btn-default" onclick="_goFullScreen(this.parentNode.parentNode);"><i class="fa fa-arrows-alt"></i></button>'
	       +
	       '  </div>'
	       +
	       '</div>'
	;

}).promise().done(function() {

	$.each($('.markdown-editor'), function() {

		var editor = $(this).find('textarea.editor');
		var viewer = $(this).find('div.viewer'     );

		editor.on('input propertychange', function() {

			viewer.html(marked(editor.val(), markdownOptions));
		});
	});
});

/*-------------------------------------------------------------------------*/
/* FILTERS                                                                 */
/*-------------------------------------------------------------------------*/

$('#category_filter').keyup(function () {

	var regExp = new RegExp($(this).val(), 'i');

	$('#category_table tbody tr').hide();

	$('#category_table tbody tr').filter(function () {

		return regExp.test($(this).text());

	}).show();
});

/*-------------------------------------------------------------------------*/

$('#page_filter').keyup(function () {

	var regExp = new RegExp($(this).val(), 'i');

	$('#page_table tbody tr').hide();

	$('#page_table tbody tr').filter(function () {

		return regExp.test($(this).text());

	}).show();
});

/*-------------------------------------------------------------------------*/

$('#article_filter').keyup(function () {

	var regExp = new RegExp($(this).val(), 'i');

	$('#article_table tbody tr').hide();

	$('#article_table tbody tr').filter(function () {

		return regExp.test($(this).text());

	}).show();
});

/*-------------------------------------------------------------------------*/

$('#menu_filter').keyup(function () {

	var regExp = new RegExp($(this).val(), 'i');

	$('#menu_table tbody tr').hide();

	$('#menu_table tbody tr').filter(function () {

		return regExp.test($(this).text());

	}).show();
});

/*-------------------------------------------------------------------------*/

$('#media_filter').keyup(function () {

	var regExp = new RegExp($(this).val(), 'i');

	$('#media_table tbody tr').hide();

	$('#media_table tbody tr').filter(function () {

		return regExp.test($(this).text());

	}).show();
});

/*-------------------------------------------------------------------------*/
/* FORMS                                                                   */
/*-------------------------------------------------------------------------*/

$('#categoryModal').on('submit', function(e) { e.preventDefault(); });
$('#pageModal').on('submit', function(e) { e.preventDefault(); });
$('#articleModal').on('submit', function(e) { e.preventDefault(); });
$('#menuModal').on('submit', function(e) { e.preventDefault(); });

/*-------------------------------------------------------------------------*/
/* MODE 'CATEGORY'                                                         */
/*-------------------------------------------------------------------------*/

function editCategory(id)
{
	/*-----------------------------------------------------------------*/

	var data = {
		getCategoryJson: id
	};

	/*-----------------------------------------------------------------*/

	$.ajax({
		url: 'mycms.php',
		type: 'POST',
		data: data,
		dataType: 'json',
	}).done(function(data) {

		$('#categoryIdInModal').val(data.id);
		$('#categoryAliasInModal').val(data.alias);
		$('#categoryTitleInModal').val(data.title);
		$('#categoryRankInModal').val(data.rank);

		$('#categoryModal').modal('show');
	});

	/*-----------------------------------------------------------------*/
}

/*-------------------------------------------------------------------------*/

function delCategory(id)
{
	if(confirm('Please confirm...') === false)
	{
		return;
	}

	/*-----------------------------------------------------------------*/

	window.location = 'admin.php?mode=categories&delCategory=' + id;

	/*-----------------------------------------------------------------*/
}

/*-------------------------------------------------------------------------*/

function formUpdateCategory(reload)
{
	if(confirm('Please confirm...') === false)
	{
		return;
	}

	/*-----------------------------------------------------------------*/

	var data = $('#categoryModal').serialize();

	/*-----------------------------------------------------------------*/

	$.ajax({
		url: 'mycms.php',
		type: 'POST',
		data: data,
	}).done(function(data) {

		if(reload)
		{
			window.location = 'admin.php?mode=categories';
		}
	});

	/*-----------------------------------------------------------------*/
}

/*-------------------------------------------------------------------------*/
/* MODE 'PAGE'                                                             */
/*-------------------------------------------------------------------------*/

function editPage(id)
{
	/*-----------------------------------------------------------------*/

	var data = {
		getPageJson: id
	};

	/*-----------------------------------------------------------------*/

	$.ajax({
		url: 'mycms.php',
		type: 'POST',
		data: data,
		dataType: 'json',
	}).done(function(data) {

		$('#pageIdInModal').val(data.id);
		$('#pageAliasInModal').val(data.alias);
		$('#pageTitleInModal').val(data.title);
		$('#pageContentInModal').val(data.content);

		if(data.visible === '1') {
			$('#pageVisibleInModal').bootstrapToggle('on');
		} else {
			$('#pageVisibleInModal').bootstrapToggle('off');
		}

		$('#pageContentInModal').trigger('propertychange');

		$('#pageModal').modal('show');
	});

	/*-----------------------------------------------------------------*/
}

/*-------------------------------------------------------------------------*/

function delPage(id)
{
	if(confirm('Please confirm...') === false)
	{
		return;
	}

	/*-----------------------------------------------------------------*/

	window.location = 'admin.php?mode=pages&delPage=' + id;

	/*-----------------------------------------------------------------*/
}

/*-------------------------------------------------------------------------*/

function formUpdatePage(reload)
{
	if(confirm('Please confirm...') === false)
	{
		return;
	}

	/*-----------------------------------------------------------------*/

	var data = $('#pageModal').serialize();

	/*-----------------------------------------------------------------*/

	$.ajax({
		url: 'mycms.php',
		type: 'POST',
		data: data,
	}).done(function(data) {

		if(reload)
		{
			window.location = 'admin.php?mode=pages';
		}
	});

	/*-----------------------------------------------------------------*/
}

/*-------------------------------------------------------------------------*/
/* MODE 'ARTICLE'                                                          */
/*-------------------------------------------------------------------------*/

function editArticle(id)
{
	/*-----------------------------------------------------------------*/

	var data = {
		getArticleJson: id
	};

	/*-----------------------------------------------------------------*/

	$.ajax({
		url: 'mycms.php',
		type: 'POST',
		data: data,
		dataType: 'json',
	}).done(function(data) {

		$('#articleIdInModal').val(data.id);
		$('#articleCategoryInModal').val(data.category);
		$('#articleAliasInModal').val(data.alias);
		$('#articleTitleInModal').val(data.title);
		$('#articleContentInModal').val(data.content);

		if(data.visible === '1') {
			$('#articleVisibleInModal').bootstrapToggle('on');
		} else {
			$('#articleVisibleInModal').bootstrapToggle('off');
		}

		$('#articleContentInModal').trigger('propertychange');

		$('#articleModal').modal('show');
	});

	/*-----------------------------------------------------------------*/
}

/*-------------------------------------------------------------------------*/

function delArticle(id)
{
	if(confirm('Please confirm...') === false)
	{
		return;
	}

	/*-----------------------------------------------------------------*/

	window.location = 'admin.php?mode=articles&delArticle=' + id;

	/*-----------------------------------------------------------------*/
}

/*-------------------------------------------------------------------------*/

function formUpdateArticle(reload)
{
	if(confirm('Please confirm...') === false)
	{
		return;
	}

	/*-----------------------------------------------------------------*/

	var data = $('#articleModal').serialize();

	/*-----------------------------------------------------------------*/

	$.ajax({
		url: 'mycms.php',
		type: 'POST',
		data: data,
	}).done(function(data) {

		if(reload)
		{
			window.location = 'admin.php?mode=articles';
		}
	});

	/*-----------------------------------------------------------------*/
}

/*-------------------------------------------------------------------------*/
/* MODE 'MENU'                                                             */
/*-------------------------------------------------------------------------*/

function editMenu(id)
{
	/*-----------------------------------------------------------------*/

	var data = {
		getMenuJson: id
	};

	/*-----------------------------------------------------------------*/

	$.ajax({
		url: 'mycms.php',
		type: 'POST',
		data: data,
		dataType: 'json',
	}).done(function(data) {

		$('#menuIdInModal').val(data.id);
		$('#menuCategoryInModal').val(data.category);
		$('#menuParentInModal').val(data.parent);
		$('#menuAliasInModal').val(data.alias);
		$('#menuTitleInModal').val(data.title);
		$('#menuRankInModal').val(data.rank);
		$('#menuPageInModal').val(data.page);
		$('#menuLinkInModal').val(data.link);

		if(data.visible === '1') {
			$('#menuVisibleInModal').bootstrapToggle('on');
		} else {
			$('#menuVisibleInModal').bootstrapToggle('off');
		}

		$('#menuModal').modal('show');
	});

	/*-----------------------------------------------------------------*/
}

/*-------------------------------------------------------------------------*/

function delMenu(id)
{
	if(confirm('Please confirm...') === false)
	{
		return;
	}

	/*-----------------------------------------------------------------*/

	window.location = 'admin.php?mode=menus&delMenu=' + id;

	/*-----------------------------------------------------------------*/
}

/*-------------------------------------------------------------------------*/

function formUpdateMenu(reload)
{
	if(confirm('Please confirm...') === false)
	{
		return;
	}

	/*-----------------------------------------------------------------*/

	var data = $('#menuModal').serialize();

	/*-----------------------------------------------------------------*/

	$.ajax({
		url: 'mycms.php',
		type: 'POST',
		data: data,
	}).done(function(data) {

		if(reload)
		{
			window.location = 'admin.php?mode=menus';
		}
	});

	/*-----------------------------------------------------------------*/
}

/*-------------------------------------------------------------------------*/
/* MODE 'MEDIA'                                                            */
/*-------------------------------------------------------------------------*/

function handleDragOver(e)
{
	e.stopPropagation();
	e.preventDefault();

	/*-----------------------------------------------------------------*/

	e.dataTransfer.dropEffect = 'copy';

	/*-----------------------------------------------------------------*/
}

/*-------------------------------------------------------------------------*/

function handleDrop(e)
{
	e.stopPropagation();
	e.preventDefault();

	/*-----------------------------------------------------------------*/

	var fd = new FormData();

	var files = e.dataTransfer.files;

	for(var i = 0, file = null; file = files[i]; i++)
	{
		fd.append('files[]', file);
	}

	fd.append('addFile', 'addFile');

	/*-----------------------------------------------------------------*/

	$.ajax({
		url: 'mycms.php',
		type: 'POST',
		data: fd,
		processData: false,
		contentType: false,
		xhr: function() {

			var xhr = new window.XMLHttpRequest();

			xhr.upload.addEventListener('progress', function(e) {

				if(e.lengthComputable)
				{
					var percent = 100 * e.loaded / e.total;

					$('#progress_bar').width(percent + '%');
				}

			}, false);

			$('#progress_bar').width('100%');

			return xhr;
		},

	}).done(function() {

		location.reload();
	});

	/*-----------------------------------------------------------------*/
}

/*-------------------------------------------------------------------------*/

var dropZone = document.getElementById('drop_zone');

if(dropZone)
{
	dropZone.addEventListener('dragover', handleDragOver, false);
	dropZone.addEventListener('drop'    , handleDrop    , false);
}

/*-------------------------------------------------------------------------*/

function renFile(oldFile)
{
	var newFile = prompt('New name', oldFile);

	if(newFile === null || newFile === ('') || newFile === oldFile)
	{
		return;
	}

	/*-----------------------------------------------------------------*/

	var data = {
		oldFile: oldFile,
		newFile: newFile,
		renFile: 'renFile',
	};

	/*-----------------------------------------------------------------*/

	$.ajax({
		url: 'mycms.php',
		type: 'POST',
		data: data,

	}).done(function() {

		location.reload();
	});

	/*-----------------------------------------------------------------*/
}

/*-------------------------------------------------------------------------*/

function delFile(file)
{
	if(confirm('Please confirm...') == false)
	{
		return;
	}

	/*-----------------------------------------------------------------*/

	var data = {
		file: file,
		delFile: 'delFile',
	};

	/*-----------------------------------------------------------------*/

	$.ajax({
		url: 'mycms.php',
		type: 'POST',
		data: data,

	}).done(function() {

		location.reload();
	});

	/*-----------------------------------------------------------------*/
}

/*-------------------------------------------------------------------------*/

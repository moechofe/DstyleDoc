<html>
{include file="_head.tpl"}
<body id="page-class">
<div id="page-content">
<div class="page-annotation"><p>{#page_class#|string_format:$class.display:$class.link:$class.file.display:$class.file.link}</p></div>
<div class="page-content">{$class}</div>
<div id="page-footer">
{include file="_footer.tpl"}
</div>
</div>
<div id="page-header">
{include file="_header.tpl"}
</div>
<div id="page-browser">
{classes_index file=$class.file}
</div>
</body>
</html>{* vim: set expandtab tabstop=2 shiftwidth=2 softtabstop=2 fileformat=unix foldmethod=marker encoding=utf8 setlocal noendofline binary *}
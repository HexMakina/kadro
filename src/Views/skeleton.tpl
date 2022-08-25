<!DOCTYPE html>
<html lang="{block name=html_lang}{/block}">
<head>{block name=html_head}
	<meta charset="UTF-8" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<meta name="author" content="hexmakina">
	<title>{block name=html_title}{/block}</title>
	{block name=html_head_link}{/block}
  <style type="text/css">{block name=html_head_style}{/block}</style>
{/block}</head>
<body {block name=body_attributes}{/block}>
{block name=body}{/block}
{block name=script_lib}{/block}
{block name=script_code}{/block}
</body>
</html>

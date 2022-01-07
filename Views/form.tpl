<form method="POST" action="{block name=form_action}{/block}" id="{block name=form_id}{/block}" class="{block name=form_class}{/block}">
{block name=form_hidden_fields}{/block}
{block name=form_header}{/block}
{block name=form_fields}{TableToForm::fields($form_model, 'w3-col l6')}{/block}
{block name=form_submit}{Form::submit('SUBMIT', 'SUBMIT')}{/block}
{block name=form_footer}{/block}
</form>

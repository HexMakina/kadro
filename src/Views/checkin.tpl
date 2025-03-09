{extends file='skeleton.tpl'}

{block name=html_title append} Checkin Station{/block}

{block name=html_head_style}
{literal}
  *{font-family:sans-serif; font-size:1em;}
  html, body {background-color: #fff; color:#000; background-image:none; text-align:center}

  #login_form{position:absolute; left:50%; top:30%; transform: translate(-50%,-30%); width:100%; max-width: 17em; padding:1em;}
  header{margin-bottom:3em;}
  img{ max-width:13em;}

  label, input{display:block; width:100%; text-align:left;}
  input{outline:none; border:0; border-bottom:1px solid #000; padding:0.5rem 0; margin:1rem 0 0.2rem 0;}
  button{
    border: none;
    display: inline-block;
    padding: 8px 16px;
    vertical-align: middle;
    overflow: hidden;
    text-decoration: none;
    text-align: center;
    cursor: pointer;
    white-space: nowrap;
    border-radius: 4px;

    width:88%; margin-top:4em; background-color:#000; color:#FFF;
  }

  .ox-warning{color:red;}

  footer{position:absolute; bottom:11px; right:11px}
{/literal}
{/block}

{block name=body}
<div id="login_form">
  <header>
    {block name=header_content}
      {block name=message_handler}{include file='messages.html'}{/block}
      {block name=header_logo}{$APP_NAME}{/block}
    {/block}
  </header>

  <form action="{block name=form_action}{$controller->router()->hyp('identify')}{/block}" method="POST">
    {Form::input('username', '', ['required', 'autofocus'])}
    <label for="username">{block name=label_username}{$controller->l('USERNAME')}{/block}</label>


    {Form::password('password')}
    <label for="password">{block name=label_password}{$controller->l('PASSWORD')}{/block}</label>

    <button type="submit">{block name=label_submit}{$controller->l('SUBMIT')}{/block}</button>
  </form>
</div>

<footer>{block name=footer_content}Powered by <a href="https://hexmakina.be">HexMakina.be</a>{/block}</footer>
{/block}

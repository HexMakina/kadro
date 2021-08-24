{block name=html_title append}
  {if $form_model->is_new()} {$html_title_subsection = L('PAGE_ACTION_CREATE')}
  {else} {$html_title_subsection = $form_model}{/if}
  {$controller->model_type_to_label($form_model)} | {$html_title_subsection}
{/block}

{block name=page_header_title}{$controller->model_type_to_label($form_model)}{/block}


{block name=page_content}
  {include file='_partials/forms/_template_loader.html'}

  {if !$form_model->is_new()}
    {block name=page_content_related_listings}{include file='_partials/related_listings.html'}{/block}
  {/if}
{/block}

{if $browse_mode}
  {if isset($this) && $this === $_element}<strong>{$_name}</strong>
  {else}<a class="link-{$_type}" href="?{$_type}={$_id|urlencode}">{$_name}</a>{/if}
{else}
  {if isset($this) && $this === $_element}<strong>{$_name}</strong>
  {else}<a class="link-{$_type}" href="{$_id}.html">{$_name}</a>{/if}
{/if}

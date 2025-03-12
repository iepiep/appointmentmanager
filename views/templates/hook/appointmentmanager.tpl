<!-- Block appointmentmanager -->
<div id="appointmentmanager_block_home" class="block">
  <h4>{l s='Welcome!' mod='appointmentmanager'}</h4>
  <div class="block_content">
    <p>Hello,
           {if isset($module_name) && $module_name}
               {$module_name}
           {else}
               World
           {/if}
           !
    </p>
    <ul>
      <li><a href="{$module_link}" title="Click this link">Click me!</a></li>
    </ul>
       <p>{$module_message}
       </p>
  </div>
</div>
<!-- /Block appointmentmanager -->
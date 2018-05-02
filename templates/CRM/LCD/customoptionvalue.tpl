{* FILE: optionvaluevisibility/templates/CRM/LCD/customoptionvalue.tpl to add custom field for custom data set*}


{section name=rowLoop start=1 loop=12}
  {assign var=index value=$smarty.section.rowLoop.index}
  {assign var=option_value value=$form.option_visible.$index.html}
  <div class="option_value{$index}">{$form.option_visible.$index.html}</div>
{literal}
    <script type="text/javascript">
      cj("div.option_value{/literal}{$index}{literal}").insertAfter('#optionField tbody tr#optionField_{/literal}{$index}{literal} td:last');
    </script>
{/literal}
{/section}

{literal}
<script type="text/javascript">
 cj("<th class='visible'>{/literal} {ts}Visible?{/ts}{literal}</th>").insertAfter('#optionField tbody tr th:last');
</script>
{/literal}  
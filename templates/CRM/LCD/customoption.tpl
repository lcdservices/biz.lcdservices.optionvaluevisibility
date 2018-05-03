{* FILE: optionvaluevisibility/templates/CRM/LCD/customoption.tpl to add custom field for custom data set*}
<table id="lcd-custom-option" class="form-layout">
  <tbody>
    <tr class="crm-custom_option-form-block-is_visible">
      <td class="label">{$form.is_visible.label}</td>
      <td>{$form.is_visible.html}</td>
    </tr>   
  </tbody>
</table>

<script type="text/javascript">
  cj('#lcd-custom-option').insertAfter('form#Option table.form-layout:first');
</script>
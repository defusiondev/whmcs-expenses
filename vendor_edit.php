<?php
if( $_SERVER["REQUEST_METHOD"] != "POST") {
    $id = filter_var($_REQUEST['id'], FILTER_VALIDATE_INT);
    $sql = "SELECT * FROM `mod_vendors` WHERE `id` = $id LIMIT 1";
    $exec = mysql_query($sql);
    $vendor = mysql_fetch_array($exec);
?>
    <form name="vendor_edit" method="post" action="<?php echo $modulelink?>&vendor=edit">
        <table>
            <tr>
                <td>
                    &nbsp;
                </td>
                <td>
                </td>
            </tr>
        </table>
        <input type="hidden" name="id" value="<?php echo $id;?>" />
        <table width="700" border="0">
          <tr valign="top">
            <td>
                <table width="100%" border="0">
                  <tr>
                    <td valign="top" class="text_newproject">Edit Vendor</td>
                  </tr>
                  <tr>
                    <td valign="top" class="txt_projectinfo">
                        Vendor Information
                    </td>
                  </tr>
                  <tr>
                    <td valign="top"><table width="100%" border="0" cellspacing="0">
                      <tr>
                        <td width="21%" class="textname_box">Vendor Name</td>
                        <td width="79%" class="text_project1">
                            <input type="text" name="vendor_name" value="<?php echo $vendor['vendor'];?>" />
                        </td>
                      </tr>
                      <tr>
                        <td class="textname_box">&nbsp;</td>
                        <td class="text_project1">
                            <input type="submit" name="submit" value="Update Vendor" />
                        </td>
                      </tr>
                      <tr>
                        <td class="textname_box">&nbsp;</td>
                        <td class="text_project1">&nbsp;</td>
                      </tr>
                    </table></td>
                  </tr>
                  <tr>
                    <td valign="top">&nbsp;</td>
                  </tr>
                  <tr>
                    <td valign="top">&nbsp;</td>
                  </tr>
                </table>
            </td>
          </tr>
        </table>
    </form>
<?php
} else {
    $vendor = filter_var($_POST['vendor_name'], FILTER_SANITIZE_STRING);
    $id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
    $sql = "
        UPDATE `mod_vendors`
        SET
            `vendor` = '$vendor'
        WHERE `id` = $id;
        ";
    if(mysql_query($sql)) {
        header("Location:$modulelink&vendor=list&msgid=23");
    } else {
        header("Location:$modulelink&vendor=list&msgid=24");
    }
}
?>
<?php
if( $_SERVER["REQUEST_METHOD"] != "POST") {
?>
    <form name="vendor_add" method="post" action="<?php echo $modulelink?>&vendor=add">
        <table width="700" border="0">
          <tr valign="top">
            <td>
                <table width="100%" border="0">
                  <tr>
                    <td valign="top" class="text_newproject">New Vendor</td>
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
                            <input type="text" name="vendor_name" />
                        </td>
                      </tr>
                      <tr>
                        <td class="textname_box">&nbsp;</td>
                        <td class="text_project1">
                            <input type="submit" name="submit" value="Add Vendor" />
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
    $time = time();
    if(trim($vendor) == '') {
        header("Location:$modulelink&vendor=list&msgid=19");
        exit();
    }
    $sql = "
        INSERT INTO `mod_vendors` (`vendor`)
        VALUES ('$vendor');
        ";
    if(mysql_query($sql)) {
        header("Location:$modulelink&vendor=list&msgid=17");
    } else {
        header("Location:$modulelink&vendor=list&msgid=18");
    }
}
?>
<?php
if( $_SERVER["REQUEST_METHOD"] != "POST") {
?>
    <form name="category_add" method="post" action="<?php echo $modulelink?>&category=add">
        <table width="700" border="0">
          <tr valign="top">
            <td>
                <table width="100%" border="0">
                  <tr>
                    <td valign="top" class="text_newproject">New Category</td>
                  </tr>
                  <tr>
                    <td valign="top" class="txt_projectinfo">
                        Category Information
                    </td>
                  </tr>
                  <tr>
                    <td valign="top"><table width="100%" border="0" cellspacing="0">
                      <tr>
                        <td width="21%" class="textname_box">Category Name</td>
                        <td width="79%" class="text_project1">
                            <input type="text" name="category_name" />
                        </td>
                      </tr>
                      <tr>
                        <td class="textname_box">&nbsp;</td>
                        <td class="text_project1">
                            <input type="submit" name="submit" value="Add Category" />
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
    $name = filter_var($_POST['category_name'], FILTER_SANITIZE_STRING);
    $time = time();
    if(trim($name) == '') {
        header("Location:$modulelink&category=list&msgid=7");
        exit();
    }
    $sql = "
        INSERT INTO `mod_categories` (`name`, `active`, `created`)
        VALUES ('$name', 1, $time);
        ";
    if(mysql_query($sql)) {
        header("Location:$modulelink&category=list&msgid=1");
    } else {
        header("Location:$modulelink&category=list&msgid=2");
    }
}
?>
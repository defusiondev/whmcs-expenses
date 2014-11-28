<?php
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    $id = filter_var($_REQUEST['id'], FILTER_VALIDATE_INT);

    $table = "mod_categories";
    $fields = " * ";
    $where = array("id" => $id);
    $exec = select_query($table, $fields,$where);
    
    $category = mysql_fetch_array($exec);
    ?>
    <form name="category_edit" method="post" action="<?php echo $modulelink ?>&category=edit">
        <table>
            <tr>
                <td>
                    &nbsp;
                </td>
                <td>
                </td>
            </tr>
        </table>
        <input type="hidden" name="id" value="<?php echo $id; ?>" />
        <table width="700" border="0">
            <tr valign="top">
                <td>
                    <table width="100%" border="0">
                        <tr>
                            <td valign="top" class="text_newproject">Edit Category</td>
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
                                            <input type="text" name="category_name" value="<?php echo $category['name']; ?>" />
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="textname_box">&nbsp;</td>
                                        <td class="text_project1">
                                            <input type="submit" name="submit" value="Update Category" />
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
    $id = filter_var($_POST['id'], FILTER_VALIDATE_INT);
    $sql = "
        UPDATE `mod_categories`
        SET
            `name` = '$name',
            `active` = 1,
            `modified` = $time
        WHERE `id` = $id;
        ";
    if (mysql_query($sql)) {
        header("Location:$modulelink&category=list&msgid=3");
    } else {
        header("Location:$modulelink&category=list&msgid=4");
    }
}
?>
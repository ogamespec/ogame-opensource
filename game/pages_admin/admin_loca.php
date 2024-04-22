<?php

// Admin Area: Check localization

function CompareTwoLocas ($src, $dst)
{
    $res = "";

    $filename = basename ($src);

    $res .= "<h2>$filename</h2>\n\n";

    // Local loca :)
    $LOCA = array();

    include $src;
    include $dst;

    $src_lang = "";
    $dst_lang = "";

    foreach ($LOCA as $i=>$lang) {
        if (strpos($src, $i)) {
            $src_lang = $i;
        }
        if (strpos($dst, $i)) {
            $dst_lang = $i;
        }        
    }    

    // List the keys from the source file:
    // - If the string matches in the target file - highlight yellow (no translation, but the string is there)
    // - If there is no string in the target file at all - highlight in red

    if (!empty($src_lang)) {
        $res .= "<table>\n";
        foreach ($LOCA[$src_lang] as $key=>$value) {

            $dst_value = "";
            $bg_col = "style=\"background-color: green;\"";
            if (key_exists($dst_lang, $LOCA) && key_exists($key, $LOCA[$dst_lang])) {
                $dst_value = $LOCA[$dst_lang][$key];
                if (!empty($value) && $value === $dst_value) {
                    $bg_col = "style=\"background-color: orange;\"";
                }
            }
            else {
                $dst_value = loca("ADM_LOCA_LOCALE_MISSING");
                $bg_col = "style=\"background-color: red;\"";
            }

            $res .= "<tr>";
            $res .= "<td $bg_col>$key</td>";
            $res .= "<td $bg_col><pre>".htmlspecialchars($value)."</pre></td>";
            $res .= "<td $bg_col><pre>".htmlspecialchars($dst_value)."</pre></td>";
            $res .= "</tr>";
        }
        $res .= "</table>\n";
    }
    else {
        $res .= "<font color=red>".loca("ADM_LOCA_FILE_MISSING")."</font><br/>\n";
    }

    return $res;
}

function Admin_Loca ()
{
    global $session;
    global $db_prefix;
    global $GlobalUser;

    $base_loca_dir = 'loca';
    $loca_dirs = scandir ($base_loca_dir);
    $diff_res = "";

    // Обработка POST-запроса.
    if ( method () === "POST" )
    {
        $src_files = scandir ($base_loca_dir . '/' . $_POST['loca_src']);
        $dst_files = scandir ($base_loca_dir . '/' . $_POST['loca_dst']);
        $processed = array();

        // Go through all the files in the source directory and compare them to the files in the target directory.

        foreach ($src_files as $i=>$file) {
            
            if ($file == '.' || $file == '..' || is_dir($base_loca_dir . '/' . $file) || !strpos($file, ".php"))
                continue;

            $diff_res .= CompareTwoLocas (
                $base_loca_dir . '/' . $_POST['loca_src'] . '/' . $file,
                $base_loca_dir . '/' . $_POST['loca_dst'] . '/' . $file );

            $processed[$file] = true;
        }
    }
?>

<?=AdminPanel();?>

<table>
<form action="index.php?page=admin&session=<?=$session;?>&mode=Loca&action=search" method="POST" >

<tr><td class="c" colspan=2><?=loca("ADM_LOCA_DIFF");?></td></tr>
<tr>
    <td>
            <?=loca("ADM_LOCA_SOURCE");?></td><td> <select name="loca_src">
<?php
    foreach ($loca_dirs as $i=>$dir) {
        
        if ($dir == '.' || $dir == '..' || !is_dir($base_loca_dir . '/' . $dir))
            continue;

        $selected = (key_exists('loca_src', $_POST) && $_POST['loca_src'] == $dir) ? "selected" : "";

        echo "<option value=\"$dir\" $selected>$dir</option>\n";
    }
?>
            </select>

<tr/><tr>
    <td>
            <?=loca("ADM_LOCA_DEST");?></td><td> <select name="loca_dst">
<?php
    foreach ($loca_dirs as $i=>$dir) {
        
        if ($dir == '.' || $dir == '..' || !is_dir($base_loca_dir . '/' . $dir))
            continue;

        $selected = (key_exists('loca_dst', $_POST) && $_POST['loca_dst'] == $dir) ? "selected" : "";

        echo "<option value=\"$dir\" $selected>$dir</option>\n";
    }
?>
            </select>

    </td>
</tr>
<tr><td class="c" colspan=2> <input type="submit" value="<?=loca("ADM_LOCA_SUBMIT");?>" /></td></tr>

</form>
</table>

<br/>

<?=$diff_res;?>

<?php
}   // Admin_Loca
?>
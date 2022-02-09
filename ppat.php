<?php

/**
 * Plugin Name: personalize page and template
 * Description: A WordPress plugin for customizing pages and templates
 * Version: 1.0.0
 * Author: m-jalali
 * Author URI: http://www.m-jalali.ir
 */

const ppat_dir_template = WP_PLUGIN_DIR . '//personalize-page-and-template//template//';

function ppat_set_template($template, $load = false, $require_once = true, $args = array())
{
    $ppat_list = unserialize(get_option("ppat_list", ''));
    if (empty($ppat_list))
        $ppat_list = array();
    foreach ($ppat_list as $idPage => $temp_file_name) {
        if (is_page($idPage)) {
            $template_path = ppat_dir_template . $temp_file_name . ".php";
            if (file_exists($template_path)) {
                if ($load)
                    return load_template($template_path, $require_once, $args);
                else
                    return $template_path;
            }
        }
    }
    return $template;
    // return locate_template($template_name);
}
add_action('template_include', 'ppat_set_template');


function ppat_add_menu()
{
    $tt_page = add_menu_page("personalize page and template", "personalize page and template", "manage_options", "ppat-panel", "ppat_admin_panel_display", null, 99);
}
add_action("admin_menu", "ppat_add_menu");

function ppat_admin_panel_display()
{
    $action = !empty($_GET) && !empty($_GET['action']) ? $_GET['action'] : "first";
    $page_id = !empty($_GET) && !empty($_GET['page_id']) ? $_GET['page_id'] : -1;

    if (!empty($_POST)) {
        $successful = true;
        $ppat_list = unserialize(get_option("ppat_list", ''));
        if (empty($ppat_list))
            $ppat_list = array();

        // ppat_page, ppat_temp_name, ppat_file_text
        // esc_textarea();
        // action Add
        if (!empty($_POST['action']) && $_POST['action'] == "add") {
            if (array_key_exists($_POST['ppat_page'], $ppat_list) === false) {
                $file_name = str_replace(' ', '-', $_POST['ppat_temp_name']) . ".php";
                $template_path =  ppat_dir_template . $file_name;
                if (!file_exists($template_path)) {
                    $myfile = fopen($template_path, "w");
                    $text = wp_unslash($_POST['ppat_file_text']);
                    $successful = fwrite($myfile, $text) === false ? false : $successful;
                    fclose($myfile);
                    $ppat_list[$_POST['ppat_page']] = str_replace('.php', '', $file_name);
                    $successful = $successful && update_option("ppat_list", serialize($ppat_list));
                } else
                    $successful = false;
            } else
                $successful = false;
        }
        // action Edit
        else if (!empty($_POST['action']) && $_POST['action'] == "edit") {
            $page_id = $_POST['page_id_last'];
            if (array_key_exists($page_id, $ppat_list) !== false) {
                $file_name = str_replace(' ', '-', $_POST['ppat_temp_name']);
                $template_path =  ppat_dir_template . $ppat_list[$page_id] . ".php";
                $is_list_change = false;
                if ($file_name != $ppat_list[$page_id] && file_exists($template_path)) {
                    unlink($template_path);
                    $is_list_change = true;
                }
                $template_path =  ppat_dir_template . $file_name . ".php";
                $myfile = fopen($template_path, "w");
                $text = wp_unslash($_POST['ppat_file_text']);
                $successful = fwrite($myfile, $text) === false ? false : $successful;
                fclose($myfile);
                if ($is_list_change || $_POST['ppat_page'] != $page_id) {
                    unset($ppat_list[$page_id]);
                    $ppat_list[$_POST['ppat_page']] = str_replace('.php', '', $file_name);
                    $successful = $successful && update_option("ppat_list", serialize($ppat_list));
                }
            } else
                $successful = false;
        }
        // action remove
        else if (!empty($_POST['action']) && $_POST['action'] == "remove" && !empty($_POST['page_id'])) {
            if (array_key_exists($_POST['page_id'], $ppat_list) !== false) {
                $file_name = $ppat_list[$_POST['ppat_page']];
                $template_path =  ppat_dir_template . $file_name . ".php";
                if (file_exists($template_path)) {
                    $successful = $successful && unlink($template_path);
                }
                unset($ppat_list[$_POST['page_id']]);
                $successful = $successful && update_option("ppat_list", serialize($ppat_list));
            } else
                $successful = false;
        } else
            $successful = false;

        if ($successful) {
            echo "<div class=\"\">successful</div>";
        } else {
            echo "<div class=\"\">un successful</div>";
        }
    }
?>
    <div class="wrap">
        <?php
        if ($action == 'add')
            ppat_add_page_display();
        else if ($action == 'edit' && $page_id != -1)
            ppat_add_page_display($page_id);
        else if ($action == 'remove' && $page_id != -1)
            ppat_remove_page_display($page_id);
        else
            ppat_first_page_display();
        ?>
    </div>
<?php
}

function ppat_remove_page_display($id)
{
?>
    <form action="admin.php?page=ppat-panel" method="POST">
        <p>Are you sure you want to delete the <a href="<?php echo get_permalink($id); ?>" target="_blank"><?php echo get_the_title($id); ?></a> page query?</p>
        <input type="hidden" name="action" value="remove">
        <input type="hidden" name="page_id" value="<?php echo $id; ?>">
        <input type="submit" value="Remove" class="button button-primary">
        <a class="button button-cancel" href="admin.php?page=ppat-panel&action=first" class="page-title-action">Cancel</a>
    </form>
<?php
}

function ppat_first_page_display()
{
    $ppat_list = unserialize(get_option("ppat_list", ''));
?>
    <style>
        .ppat_ul {
            display: block;
        }

        .ppat_ul li {}

        .ppat_ul li {
            display: inline-block;
            float: left;
        }

        .ppat_ul li:first-child::after {
            content: '';
        }

        .ppat_ul li::after {
            content: ',';
            margin-right: 5px;
            color: #ff0000;
        }
    </style>
    <h1 class="wp-heading-inline">Personalize page and template</h1>
    <div class="row"><a href="admin.php?page=ppat-panel&action=add" class="page-title-action">add</a></div>
    <table class="wp-list-table widefat fixed striped table-view-list posts">
        <thead>
            <tr>
                <th scope="col" class="manage-column">page</th>
                <th scope="col" class="manage-column">template name</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($ppat_list)) {
                foreach ($ppat_list as $page => $temp_name) { ?>
                    <tr class="">
                        <td class="">
                            <strong><a class="" href="<?php echo get_permalink($page); ?>" target="_blank" aria-label=""><?php echo get_the_title($page); ?></a></strong>
                            <div class="row-actions">
                                <span class="edit"><a href="admin.php?page=ppat-panel&action=edit&page_id=<?php echo $page; ?>" aria-label="ویرایش">ویرایش</a> | </span>
                                <span class="trash"><a href="admin.php?page=ppat-panel&action=remove&page_id=<?php echo $page; ?>" class="submitdelete" aria-label="حذف">حذف</a> | </span>
                                <span class="view"><a href="<?php echo get_permalink($page); ?>" target="_blank" rel="bookmark" aria-label="نمایش">نمایش</a></span>
                            </div>
                        </td>
                        <td class="">
                            <?php echo $temp_name; ?>
                        </td>
                    </tr>
            <?php }
            } else echo '<tr class=""><td>null</td></tr>'; ?>
        </tbody>
        <tfoot>
            <tr>
                <th scope="col" class="manage-column">page</th>
                <th scope="col" class="manage-column">template name</th>
            </tr>
        </tfoot>

    </table>
<?php
}


function ppat_add_page_display($pid = false)
{
    $text = "";
    $temp_name = "";
    $template_path = "";
    if ($pid !== false) {
        $ppat_list = unserialize(get_option("ppat_list", ''));
        if (array_key_exists($pid, $ppat_list)) {
            $template_path = ppat_dir_template . $ppat_list[$pid] . ".php";
            if (file_exists($template_path)) {
                $temp_name = $ppat_list[$pid];
                $myfile = fopen($template_path, "r") or die("Unable to open file!");
                $text = fread($myfile, filesize($template_path));
                fclose($myfile);
            }
        }
    }
    $settings = array(
        'codeEditor' => wp_enqueue_code_editor(array('file' => $template_path)),
    );
    wp_enqueue_script('wp-theme-plugin-editor');
    wp_add_inline_script('wp-theme-plugin-editor', sprintf('jQuery( function( $ ) { wp.themePluginEditor.init( $( "#ppat_form_" ), %s ); } )', wp_json_encode($settings)));
    // wp_add_inline_script('wp-theme-plugin-editor', 'wp.themePluginEditor.themeOrPlugin = "theme";');
?>
    <style>
        .ppat_row {
            padding: 20px;
        }

        .ppat_row label {
            display: inline-block;
            width: 20%;
        }

        .ppat_row .ppat_sec {
            display: inline-block;
            width: 70%;
        }

        .ppat_row .ppat_sec input[type=text],
        .ppat_row .ppat_sec input[type=number],
        .ppat_row .ppat_sec select,
        .ppat_row .ppat_sec textarea {
            width: 30%;
        }
    </style>
    <h1 class="wp-heading-inline">Add Personalize page and template</h1>
    <div class="row"><a href="admin.php?page=ppat-panel&action=first" class="page-title-action">back</a></div>
    <form id="ppat_form" action="admin.php?page=ppat-panel" method="post">
        <div class="ppat_row">
            <label for="ppat_page">Select page</label>
            <div class="ppat_sec">
                <select name="ppat_page" id="">
                    <?php $pages = get_pages();
                    foreach ($pages as $page) { ?>
                        <option value="<?php echo $page->ID ?>" <?php echo ($pid && $pid == $page->ID) ? 'selected' : ''; ?>><?php echo $page->post_title ?></option>
                    <?php } ?>
                </select>
            </div>
        </div>
        <div class="ppat_row">
            <label for="ppat_temp_name">template name</label>
            <div class="ppat_sec">
                <input type="text" name="ppat_temp_name" value="<?php echo $pid !== false ? $temp_name : ''; ?>">
            </div>
        </div>
        <div class="ppat_row">
            <textarea name="ppat_file_text" id="newcontent" cols="30" rows="20"><?php echo ($pid !== false) ? $text : ''; ?></textarea>
        </div>
        <div class="ppat_row">
            <input type="hidden" name="page_id_last" value="<?php echo $pid ? $pid : ''; ?>">
            <input type="hidden" name="action" value="<?php echo $pid ? 'edit' : 'add'; ?>">
            <input class="button button-primary" type="submit" name="submit" value="<?php echo $pid ? 'Save' : 'Add'; ?>">
            <a class="button button-cancel" href="admin.php?page=ppat-panel&action=first" class="page-title-action">back</a>
        </div>
    </form>
<?php
}

<?php
if (!defined('ABSPATH')) {
    exit;
}

require_once(EL_PATH . 'includes/db.php');
require_once(EL_PATH . 'includes/categories.php');
define(EL_IMPORT_PATH, EL_URL . 'example/termine.csv');

// This class handles all data for the admin new event page
class EL_Admin_Import
{
    private static $instance;
    private $db;
    private $categories;

    private $import_data;

    public static function &get_instance()
    {
        // Create class instance if required
        if (!isset(self::$instance)) {
            self::$instance = new EL_Admin_Import();
        }

        // Return class instance
        return self::$instance;
    }

    private function __construct()
    {
        $this->db = & EL_Db::get_instance();
        $this->categories = & EL_Categories::get_instance();
    }

    public function show_import()
    {
        if (!current_user_can('edit_posts')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        ?>
        <div class="wrap">
            <div id="icon-edit-pages" class="icon32"><br /></div>
            <h2>Import Events</h2>
            <?php $this->show_import_form(); ?>
        </div>
    <?php
    }

    public function show_import_form()
    {
        if (isset($_FILES['el_import_file'])) :

            $this->show_review();

        elseif (isset($_POST['reviewed_events'])) :

            $reviewed_events = unserialize(stripslashes($_POST['reviewed_events']));
            $categories = $_POST['categories'];

            if (isset($categories)) :

                foreach ($reviewed_events as &$event) :
                    $event['categories'] = $categories;
                endforeach;

            endif;

            $returnValues = array();
            foreach ($reviewed_events as &$event) {
                // convert date format to be SQL-friendly
                $myDateTime = DateTime::createFromFormat('d.m.Y', $event['start_date']);
                $event['start_date'] = $myDateTime->format('Y-m-d');
                $myDateTime = DateTime::createFromFormat('d.m.Y', $event['end_date']);
                $event['end_date'] = $myDateTime->format('Y-m-d');

                $returnValues[] = $this->db->update_event($event);
            }

            if (in_array(false, $returnValues)) :
                echo '<h3>Import with errors!</h3>';
                echo 'An error occurred during import! Please send your import file to <a href="mailto:' . get_option( 'admin_email' ) . '">the administrator</a> for analysis.';
            else :
                echo '<h3>Import successful!</h3>';
                echo 'Please return to overview <a href="?page=el_admin_main">' . __('here') . '</a>!';
            endif;

        else :
            ?>

            <form action="" id="el_import_upload" method="post" enctype="multipart/form-data">
                <p>
                    Select a file that contains event data.
                </p>

                <p>
                    <input name="el_import_file" type="file" size="50" maxlength="100000">
                </p>
                <input type="submit" name="button-upload-submit" id="button-upload-submit" class="button"
                       value="Import Event Data" />
                <br><br>
            </form>

            <h3>Example file</h3>
            <p>
                Please find an example file <a href="<?php echo EL_IMPORT_PATH; ?>">here</a>. (CSV delimiter is a comma!)<br>
                Note: <em>Do not change the column header and separator line (first two lines), otherwise the import will fail!</em>
            </p>

        <?php

        endif;
    }

    private function show_review()
    {
        $file = $_FILES["el_import_file"]["tmp_name"];

        # check for file existence (upload failed?)
        if (!is_file($file)) {
            echo '<h3>' . __('Sorry, there has been an error.', 'event-list') . '</h3>';
            echo __('The file does not exist, please try again.', 'event-list') . '</p>';

            return;
        }

        # check for file extension (csv) first
        $file_parts = pathinfo($_FILES["el_import_file"]["name"]);
        if ($file_parts['extension'] !== "csv") {
            echo '<h3>' . __('Sorry, there has been an error.', 'event-list') . '</h3>';
            echo __('The file is not an CSV file.', 'event-list') . '</p>';

            return;
        }

        # parse file
        $import_data = $this->parseImportFile($file);

        # parsing failed?
        if (is_wp_error($import_data)) {
            echo '<h3>' . __('Sorry, there has been an error.', 'event-list') . '</h3>';
            echo '<p>' . esc_html($import_data->get_error_message()) . '</p>';

            return;
        }

        $this->import_data = $import_data;
        $serialized = serialize($this->import_data);

        ?>

        <h3>Please review the events to import and choose categories before importing.</h3>

        <form method="POST" action="?page=el_admin_import">
            <?php
            wp_nonce_field('autosavenonce', 'autosavenonce', false, false);
            wp_nonce_field('closedpostboxesnonce', 'closedpostboxesnonce', false, false);
            wp_nonce_field('meta-box-order-nonce', 'meta-box-order-nonce', false, false);
            ?>
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <div id="post-body-content">
                        <?php foreach ($this->import_data as $event) :
                            $this->show_event($event);
                        endforeach; ?>
                    </div>
                    <div id="postbox-container-1" class="postbox-container">
                        <div id="side-sortables" class="meta-box-sortables ui-sortable">
                            <?php
                            add_meta_box('event-categories', __('Categories'), array(&$this, 'render_category_metabox'), 'event-list', 'advanced', 'default', null);
                            add_meta_box('event-publish', __('Import'), array(&$this, 'render_publish_metabox'), 'event-list');
                            ob_start();
                            do_meta_boxes('event-list', 'advanced', null);
                            $out = ob_get_contents();
                            ob_end_clean();
                            echo $out;
                            ?>
                        </div>
                    </div>
                </div>
            </div>
            <input type="hidden" name="reviewed_events" id="reviewed_events"
                   value="<?php echo esc_html($serialized); ?>" />
        </form>

    <?php
    }

    private function show_event($event)
    {
        ?>
        <p>
            <span style="font-weight: bold;">Titel:</span>
            <span style="font-style: italic;"><?php echo $event["title"]; ?></span>
            <br>
            <span style="font-weight: bold;">Startdatum:</span>
            <span style="font-style: italic;"><?php echo $event["start_date"]; ?></span>
            <br>
            <span style="font-weight: bold;">Enddatum:</span>
            <span style="font-style: italic;"><?php echo $event["end_date"]; ?></span>
            <br>
            <span style="font-weight: bold;">Uhrzeit:</span>
            <span style="font-style: italic;"><?php echo $event["time"]; ?></span>
            <br>
            <span style="font-weight: bold;">Ort:</span>
            <span style="font-style: italic;"><?php echo $event["location"]; ?></span>
            <br>
            <span style="font-weight: bold;">Details:</span>
            <span style="font-style: italic;"><?php echo $event["details"]; ?></span>
        </p>
    <?php

    }

    /**
     * @return WP_Error
     */
    private function parseImportFile($file)
    {
        $delimiter = ",";
        $header = array("Titel", "Startdatum", "Enddatum", "Uhrzeit", "Ort", "Details");
        $separator = array("sep=,");

        // list of events to import
        $events = array();


        $file_handle = fopen($file, 'r');
        $lineNum = 0;
        while (!feof($file_handle)) {
            $line = fgetcsv($file_handle, 1024);

            // skip empty line
            if (empty($line)) continue;

            if ($lineNum === 0) {
                if ($line === $separator)
                    continue;

                if ($line === $header):
                    $lineNum += 1;
                    continue;
                else :
                    var_dump($line);
                    var_dump($header);
                    return new WP_Error('CSV_parse_error', __('There was an error when reading this CSV file.', 'event-list'));
                endif;

            }

            $events[] = array(
                "title" => $line[0],
                "start_date" => $line[1],
                "end_date" => !empty($line[2]) ? $line[2] : $line[1],
                "time" => $line[3],
                "location" => $line[4],
                "details" => $line[5]
            );

            $lineNum += 1;
        }

        //close file
        fclose($file_handle);

        return $events;
    }

    public function render_publish_metabox()
    {
        $out = '<div class="submitbox">
   				<div id="delete-action"><a href="?page=el_admin_main" class="submitdelete deletion">' . __('Cancel') . '</a></div>
   				<div id="publishing-action"><input type="submit" class="button button-primary button-large" name="import" value="' . __('Import') . '" id="import"></div>
   				<div class="clear"></div>
   			</div>';
        echo $out;
    }

    public function render_category_metabox($post, $metabox)
    {
        $out = '
   				<div id="taxonomy-category" class="categorydiv">
   				<div id="category-all" class="tabs-panel">';
        $cat_array = $this->categories->get_cat_array('name', 'asc');
        if (empty($cat_array)) {
            $out .= __('No categories available.');
        } else {
            $out .= '
   					<ul id="categorychecklist" class="categorychecklist form-no-clear">';
            $level = 0;
            $event_cats = explode('|', substr($metabox['args']['event_cats'], 1, -1));
            foreach ($cat_array as $cat) {
                if ($cat['level'] > $level) {
                    //new sub level
                    $out .= '
   						<ul class="children">';
                    $level++;
                }
                while ($cat['level'] < $level) {
                    // finish sub level
                    $out .= '
   						</ul>';
                    $level--;
                }
                $level = $cat['level'];
                $checked = in_array($cat['slug'], $event_cats) ? 'checked="checked" ' : '';
                $out .= '
   						<li id="' . $cat['slug'] . '" class="popular-catergory">
   							<label class="selectit">
   								<input value="' . $cat['slug'] . '" type="checkbox" name="categories[]" id="categories" ' . $checked . '/> ' . $cat['name'] . '
   							</label>
   						</li>';
            }
            $out .= '
   					</ul>';
        }

        $out .= '</div>';
        $out .= '</div>';
        echo $out;
    }

}

?>

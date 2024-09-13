<?php

/**
 * @package   Duplicator
 * @copyright (c) 2022, Snap Creek LLC
 */

namespace Duplicator\Ajax;

use DUP_PRO_Archive;
use DUP_PRO_Archive_Build_Mode;
use DUP_PRO_DATE;
use DUP_PRO_Global_Entity;
use DUP_PRO_Handler;
use DUP_PRO_Log;
use DUP_PRO_Package;
use DUP_PRO_Package_File_Type;
use DUP_PRO_Package_Runner;
use DUP_PRO_Package_Template_Entity;
use DUP_PRO_Package_Upload_Info;
use DUP_PRO_PackageStatus;
use DUP_PRO_Thread_Lock_Mode;
use DUP_PRO_Tree_files;
use DUP_PRO_U;
use DUP_PRO_Upload_Status;
use DUP_PRO_ZipArchive_Mode;
use Duplicator\Addons\ProBase\License\License;
use Duplicator\Core\CapMng;
use Duplicator\Core\Views\TplMng;
use Duplicator\Libs\Snap\SnapUtil;
use Duplicator\Models\Storages\AbstractStorageEntity;
use Duplicator\Models\Storages\StoragesUtil;
use Error;
use Exception;
use stdClass;

class ServicesPackage extends AbstractAjaxService
{
    const EXEC_STATUS_PASS             = 1;
    const EXEC_STATUS_WARN             = 2;
    const EXEC_STATUS_FAIL             = 3;
    const EXEC_STATUS_INCOMPLETE       = 4; // Still more to go
    const EXEC_STATUS_SCHEDULE_RUNNING = 5;

    /**
     * Init ajax calls
     *
     * @return void
     */
    public function init()
    {
        $this->addAjaxCall('wp_ajax_duplicator_pro_process_worker', 'processWorker');
        $this->addAjaxCall('wp_ajax_nopriv_duplicator_pro_process_worker', 'processWorker');

        $this->addAjaxCall('wp_ajax_duplicator_pro_download_package_file', 'downloadPackageFile');
        $this->addAjaxCall('wp_ajax_nopriv_duplicator_pro_download_package_file', 'downloadPackageFile');

        if (!License::can(License::CAPABILITY_PRO_BASE)) {
            return;
        }
        $this->addAjaxCall('wp_ajax_duplicator_add_quick_filters', 'addQuickFilters');
        $this->addAjaxCall('wp_ajax_duplicator_pro_package_scan', 'packageScan');
        $this->addAjaxCall('wp_ajax_duplicator_pro_package_delete', 'packageDelete');
        $this->addAjaxCall('wp_ajax_duplicator_pro_reset_packages', 'resetPackages');
        $this->addAjaxCall('wp_ajax_duplicator_pro_get_package_statii', 'packageStatii');
        $this->addAjaxCall('wp_ajax_duplicator_pro_package_stop_build', 'stopBuild');
        $this->addAjaxCall('wp_ajax_duplicator_pro_manual_transfer_storage', 'manualTransferStorage');
        $this->addAjaxCall('wp_ajax_duplicator_pro_packages_details_transfer_get_package_vm', 'detailsTransferGetPackageVM');
        $this->addAjaxCall('wp_ajax_duplicator_pro_get_folder_children', 'getFolderChildren');
        $this->addAjaxCall("wp_ajax_duplicator_get_remote_restore_download_options", "remoteRestoreDownloadOptions");
    }

    /**
     * Removed all reserved installer files names
     *
     * @return never
     */
    public function addQuickFilters()
    {
        DUP_PRO_Handler::init_error_handler();
        check_ajax_referer('duplicator_add_quick_filters', 'nonce');
        $inputData = filter_input_array(INPUT_POST, array(
            'dir_paths'  => array(
                'filter'  => FILTER_DEFAULT,
                'flags'   => FILTER_REQUIRE_SCALAR,
                'options' => array('default' => ''),
            ),
            'file_paths' => array(
                'filter'  => FILTER_DEFAULT,
                'flags'   => FILTER_REQUIRE_SCALAR,
                'options' => array('default' => ''),
            ),
        ));
        $result    = [
            'success'      => false,
            'message'      => '',
            'filter-dirs'  => '',
            'filter-files' => '',
            'filter-names' => '',
        ];
        try {
            // CONTROLLER LOGIC
            // Need to update both the template and the temporary package because:
            // 1) We need to preserve preferences of this build for future manual builds - the manual template is used for this.
            // 2) Temporary package is used during this build - keeps all the settings/storage information.
            // Will be inserted into the package table after they ok the scan results.
            $template  = DUP_PRO_Package_Template_Entity::get_manual_template();
            $dirPaths  = DUP_PRO_Archive::parseDirectoryFilter(SnapUtil::sanitizeNSChars($inputData['dir_paths']));
            $filePaths = DUP_PRO_Archive::parseFileFilter(SnapUtil::sanitizeNSChars($inputData['file_paths']));

            // If we are adding a new filter & we have filters disabled, clear out the old filters.
            if (!$template->archive_filter_on && (strlen($dirPaths) > 0 || strlen($filePaths) > 0)) {
                $template->archive_filter_dirs  = '';
                $template->archive_filter_files = '';
            }

            if (strlen($dirPaths) > 0) {
                $template->archive_filter_dirs .= strlen($template->archive_filter_dirs) > 0 ? ';' . $dirPaths : $dirPaths;
            }

            if (strlen($filePaths) > 0) {
                $template->archive_filter_files .= strlen($template->archive_filter_files) > 0 ? ';' . $filePaths : $filePaths;
            }

            if (!$template->archive_filter_on) {
                $template->archive_filter_exts = '';
            }

            $template->archive_filter_on    = 1;
            $template->archive_filter_names = true;
            $template->save();

            $temporary_package                       = DUP_PRO_Package::get_temporary_package();
            $temporary_package->Archive->FilterDirs  = $template->archive_filter_dirs;
            $temporary_package->Archive->FilterFiles = $template->archive_filter_files;
            $temporary_package->Archive->FilterOn    = true;
            $temporary_package->Archive->FilterNames = $template->archive_filter_names;
            $temporary_package->set_temporary_package();

            $result['success']      = true;
            $result['filter-dirs']  = $temporary_package->Archive->FilterDirs;
            $result['filter-files'] = $temporary_package->Archive->FilterFiles;
            $result['filter-names'] = $temporary_package->Archive->FilterNames;
        } catch (Exception $exc) {
            $result['success'] = false;
            $result['message'] = $exc->getMessage();
        }

        wp_send_json($result);
    }

    /**
     *  DUPLICATOR_PRO_PACKAGE_SCAN
     *
     *  @example to test: /wp-admin/admin-ajax.php?action=duplicator_pro_package_scan
     *
     *  @return void
     */
    public function packageScan()
    {
        AjaxWrapper::json(
            array(
                __CLASS__,
                'packageScanCallback',
            ),
            'duplicator_pro_package_scan',
            SnapUtil::sanitizeTextInput(SnapUtil::INPUT_REQUEST, 'nonce'),
            CapMng::CAP_CREATE
        );
    }

    /**
     *  DUPLICATOR_PRO_PACKAGE_SCAN
     *
     *  @example to test: /wp-admin/admin-ajax.php?action=duplicator_pro_package_scan
     *
     *  @return array<string, mixed>
     */
    public static function packageScanCallback()
    {
        DUP_PRO_Handler::init_error_handler();
        try {
            $global = DUP_PRO_Global_Entity::getInstance();

            // Keep the locking file opening and closing just to avoid adding even more complexity
            $locking_file = true;
            if ($global->lock_mode == DUP_PRO_Thread_Lock_Mode::Flock) {
                $locking_file = fopen(DUPLICATOR_PRO_LOCKING_FILE_FILENAME, 'c+');
            }

            if ($locking_file != false) {
                if ($global->lock_mode == DUP_PRO_Thread_Lock_Mode::Flock) {
                    $acquired_lock = (flock($locking_file, LOCK_EX | LOCK_NB) != false);
                    if ($acquired_lock) {
                        DUP_PRO_Log::trace("File lock acquired " . DUPLICATOR_PRO_LOCKING_FILE_FILENAME);
                    } else {
                        DUP_PRO_Log::trace("File lock denied " . DUPLICATOR_PRO_LOCKING_FILE_FILENAME);
                    }
                } else {
                    $acquired_lock = DUP_PRO_U::getSqlLock();
                }

                if ($acquired_lock) {
                    @set_time_limit(0);
                    StoragesUtil::getDefaultStorage()->initStorageDirectory(true);

                    $package     = DUP_PRO_Package::get_temporary_package();
                    $package->ID = null;
                    $report      = $package->create_scan_report();
                    //After scanner runs save FilterInfo (unreadable, warnings, globals etc)
                    $package->set_temporary_package();

                    //delif($package->Archive->ScanStatus == DUP_PRO_Archive::ScanStatusComplete){
                    $report['Status'] = self::EXEC_STATUS_PASS;

                    // The package has now been corrupted with directories and scans so cant reuse it after this point
                    DUP_PRO_Package::set_temporary_package_member('ScanFile', $package->ScanFile);
                    DUP_PRO_Package::tmp_cleanup();
                    DUP_PRO_Package::set_temporary_package_member('Status', DUP_PRO_PackageStatus::AFTER_SCAN);

                    //del}

                    if ($global->lock_mode == DUP_PRO_Thread_Lock_Mode::Flock) {
                        if (!flock($locking_file, LOCK_UN)) {
                            DUP_PRO_Log::trace("File lock can't release " . $locking_file);
                        } else {
                            DUP_PRO_Log::trace("File lock released " . $locking_file);
                        }
                        fclose($locking_file);
                    } else {
                        DUP_PRO_U::releaseSqlLock();
                    }
                } else {
                    // File is already locked indicating schedule is running
                    $report['Status'] = self::EXEC_STATUS_SCHEDULE_RUNNING;
                    DUP_PRO_Log::trace("Already locked when attempting manual build - schedule running");
                }
            } else {
                // Problem opening the locking file report this is a critical error
                $report['Status'] = self::EXEC_STATUS_FAIL;

                DUP_PRO_Log::trace("Problem opening locking file so auto switching to SQL lock mode");
                $global->lock_mode = DUP_PRO_Thread_Lock_Mode::SQL_Lock;
                $global->save();
            }
        } catch (Exception $ex) {
            return array(
                'Status'  =>  3,
                'Message' => sprintf(__("Exception occurred. Exception message: %s", 'duplicator-pro'), $ex->getMessage()),
                'File'    => $ex->getFile(),
                'Line'    => $ex->getLine(),
                'Trace'   => $ex->getTrace(),
            );
        } catch (Error $ex) {
            return array(
                'Status'  =>  3,
                'Message' =>  sprintf(
                    esc_html__("Fatal Error occurred. Error message: %1\$s<br>\nTrace: %2\$s", 'duplicator-pro'),
                    $ex->getMessage(),
                    $ex->getTraceAsString()
                ),
                'File'    => $ex->getFile(),
                'Line'    => $ex->getLine(),
                'Trace'   => $ex->getTrace(),
            );
        }

        return $report;
    }

    /**
     * Hook ajax wp_ajax_duplicator_pro_package_delete
     * Deletes the files and database record entries
     *
     * @return void
     */
    public function packageDelete()
    {
        AjaxWrapper::json(
            array(
                __CLASS__,
                'packageDeleteCallback',
            ),
            'duplicator_pro_package_delete',
            SnapUtil::sanitizeTextInput(SnapUtil::INPUT_REQUEST, 'nonce'),
            CapMng::CAP_CREATE
        );
    }

    /**
     * Hook ajax wp_ajax_duplicator_pro_package_delete
     * Deletes the files and database record entries
     *
     * @return array<string, mixed>
     */
    public static function packageDeleteCallback()
    {
        $deletedCount = 0;

        $inputData     = filter_input_array(INPUT_POST, array(
            'package_ids' => array(
                'filter'  => FILTER_VALIDATE_INT,
                'flags'   => FILTER_REQUIRE_ARRAY,
                'options' => array('default' => false),
            ),
        ));
        $packageIDList = $inputData['package_ids'];

        if (empty($packageIDList) || in_array(false, $packageIDList)) {
            throw new Exception(__("Invalid request.", 'duplicator-pro'));
        }

        DUP_PRO_Log::traceObject("Starting deletion of packages by ids: ", $packageIDList);
        foreach ($packageIDList as $id) {
            if ($package = DUP_PRO_Package::get_by_id($id)) {
                if ($package->delete()) {
                    $deletedCount++;
                }
            } else {
                throw new Exception("Invalid package ID.");
            }
        }

        return [
            'ids'     => $packageIDList,
            'removed' => $deletedCount,
        ];
    }

    /**
     * Hook ajax wp_ajax_duplicator_pro_reset_packages
     *
     * @return never
     */
    public function resetPackages()
    {
        ob_start();
        try {
            DUP_PRO_Handler::init_error_handler();

            $error  = false;
            $result = array(
                'data'    => array('status' => null),
                'html'    => '',
                'message' => '',
            );

            $nonce = SnapUtil::sanitizeTextInput(INPUT_POST, 'nonce');
            if (!wp_verify_nonce($nonce, 'duplicator_pro_reset_packages')) {
                DUP_PRO_Log::trace('Security issue');
                throw new Exception('Security issue');
            }
            CapMng::can(CapMng::CAP_SETTINGS);

            // first last package id
            $ids = DUP_PRO_Package::get_ids_by_status(
                array(array('op' => '<', 'status' => DUP_PRO_PackageStatus::COMPLETE)),
                0,
                0,
                '`id` DESC'
            );
            foreach ($ids as $id) {
                // A smooth deletion is not performed because it is a forced reset.
                DUP_PRO_Package::force_delete($id);
            }
        } catch (Exception $e) {
            $error             = true;
            $result['message'] = $e->getMessage();
        }

        $result['html'] = ob_get_clean();
        if ($error) {
            wp_send_json_error($result);
        } else {
            wp_send_json_success($result);
        }
    }

    /**
     * Hook ajax wp_ajax_duplicator_pro_get_package_statii
     *
     * @return void
     */
    public function packageStatii()
    {
        AjaxWrapper::json(
            array(
                __CLASS__,
                'packageStatiiCallback',
            ),
            'duplicator_pro_get_package_statii',
            SnapUtil::sanitizeTextInput(INPUT_POST, 'nonce'),
            CapMng::CAP_BASIC
        );
    }

    /**
     * Hook ajax wp_ajax_duplicator_pro_get_package_statii
     *
     * @return array<mixed>
     */
    public static function packageStatiiCallback()
    {
        $limit     = SnapUtil::sanitizeIntInput(SnapUtil::INPUT_REQUEST, 'limit', 0);
        $offset    = SnapUtil::sanitizeIntInput(SnapUtil::INPUT_REQUEST, 'offset', 0);
        $packageId = SnapUtil::sanitizeIntInput(SnapUtil::INPUT_REQUEST, 'packageId', -1);

        if ($packageId > 0) {
            if (($package = DUP_PRO_Package::get_by_id($packageId)) === false) {
                throw new Exception(__('Couldn\'t get package.', 'duplicator-pro'));
            }

            return [self::getPackageStatusInfo($package)];
        }

        $resultData = [];
        DUP_PRO_Package::by_status_callback(
            function (DUP_PRO_Package $package) use (&$resultData) {
                $resultData[] = self::getPackageStatusInfo($package);
            },
            [],
            $limit,
            $offset,
            '`id` DESC'
        );

        return $resultData;
    }

    /**
     * Returns the package status info
     *
     * @param DUP_PRO_Package $package The package
     *
     * @return array<string, mixed> The status data
     */
    protected static function getPackageStatusInfo(DUP_PRO_Package $package)
    {
        $status                         = [];
        $status['ID']                   = $package->ID;
        $status['status']               = self::getAdjustedPackageStatus($package);
        $status['status_progress']      = $package->get_status_progress();
        $status['size']                 = $package->get_display_size();
        $status['status_progress_text'] = '';

        if ($status['status'] < DUP_PRO_PackageStatus::COMPLETE) {
            $active_storage = $package->get_active_storage();
            if ($active_storage !== false) {
                $isDownload                     = $package->isDownloadInProgress();
                $status['status_progress_text'] = $active_storage->getActionText($isDownload);
            } else {
                $status['status_progress_text'] = '';
            }
        }

        return $status;
    }

    /**
     * Get the package status
     *
     * @param DUP_PRO_Package $package The package to get the status for
     *
     * @return int|float
     */
    private static function getAdjustedPackageStatus(DUP_PRO_Package $package)
    {
        $estimated_progress = ($package->build_progress->current_build_mode == DUP_PRO_Archive_Build_Mode::Shell_Exec) ||
            ($package->ziparchive_mode == DUP_PRO_ZipArchive_Mode::SingleThread);

        if (($package->Status == DUP_PRO_PackageStatus::ARCSTART) && $estimated_progress) {
            // Amount of time passing before we give them a 1%
            $time_per_percent       = 11;
            $thread_age             = time() - $package->build_progress->thread_start_time;
            $total_percentage_delta = DUP_PRO_PackageStatus::ARCDONE - DUP_PRO_PackageStatus::ARCSTART;

            if ($thread_age > ($total_percentage_delta * $time_per_percent)) {
                // It's maxed out so just give them the done condition for the rest of the time
                return DUP_PRO_PackageStatus::ARCDONE;
            } else {
                $percentage_delta = (int) ($thread_age / $time_per_percent);

                return DUP_PRO_PackageStatus::ARCSTART + $percentage_delta;
            }
        } else {
            return $package->Status;
        }
    }

    /**
     * Hook ajax wp_ajax_duplicator_pro_package_stop_build
     *
     * @return void
     */
    public function stopBuild()
    {
        AjaxWrapper::json(
            array(
                __CLASS__,
                'stopBuildCallback',
            ),
            'duplicator_pro_package_stop_build',
            SnapUtil::sanitizeTextInput(INPUT_POST, 'nonce'),
            CapMng::CAP_CREATE
        );
    }

    /**
     * Stop build callback
     *
     * @return array<string, mixed>
     */
    public static function stopBuildCallback()
    {
        $inputData = filter_input_array(INPUT_POST, array(
            'package_id'  => array(
                'filter'  => FILTER_VALIDATE_INT,
                'flags'   => FILTER_REQUIRE_SCALAR,
                'options' => array('default' => false),
            ),
            'stop_active' => array(
                'filter'  => FILTER_VALIDATE_BOOLEAN,
                'flags'   => FILTER_REQUIRE_SCALAR,
                'options' => array('default' => false),
            ),
        ));

        $packageId  = $inputData['package_id'];
        $stopActive = $inputData['stop_active'];

        try {
            $package = null;
            if ($stopActive) {
                DUP_PRO_Log::trace("Web service stop build of $packageId");
                $package = DUP_PRO_Package::get_next_active_package();
            } elseif ($packageId != false) {
                DUP_PRO_Log::trace("Web service stop build of $packageId");
                $package = DUP_PRO_Package::get_by_id($packageId);
            }

            if ($package == null && $stopActive !== true) {
                DUP_PRO_Log::trace(
                    "Could not find package so attempting hard delete. 
                    Old files may end up sticking around although chances are there isnt much if we couldnt nicely cancel it."
                );
                $result = DUP_PRO_Package::force_delete($packageId);

                if (!$result) {
                    throw new Exception('Hard delete failure');
                }

                return [
                    'success' => true,
                    'message' => 'Hard delete success',
                ];
            } else {
                DUP_PRO_Log::trace("set $package->ID for cancel");
                $package->set_for_cancel();
            }
        } catch (Exception $ex) {
            DUP_PRO_Log::trace($ex->getMessage());
            throw $ex;
        }

        return ['success' => true];
    }

    /**
     * Hook ajax process worker
     *
     * @return never
     */
    public function processWorker()
    {
        DUP_PRO_Handler::init_error_handler();
        DUP_PRO_U::checkAjax();
        header("HTTP/1.1 200 OK");

        /*
          $nonce = sanitize_text_field($_REQUEST['nonce']);
          if (!wp_verify_nonce($nonce, 'duplicator_pro_process_worker')) {
          DUP_PRO_Log::trace('Security issue');
          die('Security issue');
          }
         */

        DUP_PRO_Log::trace("Process worker request");

        DUP_PRO_Package_Runner::process();

        DUP_PRO_Log::trace("Exiting process worker request");

        echo 'ok';
        exit();
    }

    /**
     * Hook ajax wp_ajax_duplicator_pro_download_package_file
     *
     * @return never
     */
    public function downloadPackageFile()
    {
        DUP_PRO_Handler::init_error_handler();
        $inputData = filter_input_array(INPUT_GET, array(
            'fileType' => array(
                'filter'  => FILTER_VALIDATE_INT,
                'flags'   => FILTER_REQUIRE_SCALAR,
                'options' => array('default' => false),
            ),
            'hash'     => array(
                'filter'  => FILTER_SANITIZE_SPECIAL_CHARS,
                'flags'   => FILTER_REQUIRE_SCALAR,
                'options' => array('default' => false),
            ),
            'token'    => array(
                'filter'  => FILTER_SANITIZE_SPECIAL_CHARS,
                'flags'   => FILTER_REQUIRE_SCALAR,
                'options' => array('default' => false),
            ),
        ));

        try {
            if (
                $inputData['token'] === false ||
                $inputData['hash'] === false ||
                $inputData["fileType"] === false ||
                DUP_PRO_Package::getLocalPackageAjaxDownloadToken($inputData['hash']) !== $inputData['token'] ||
                ($package = DUP_PRO_Package::get_by_hash($inputData['hash'])) == false
            ) {
                throw new Exception(__("Invalid request.", 'duplicator-pro'));
            }

            switch ($inputData['fileType']) {
                case DUP_PRO_Package_File_Type::Installer:
                    $filePath = $package->getLocalPackageFilePath(DUP_PRO_Package_File_Type::Installer);
                    $fileName = $package->Installer->getDownloadName();
                    break;
                case DUP_PRO_Package_File_Type::Archive:
                    $filePath = $package->getLocalPackageFilePath(DUP_PRO_Package_File_Type::Archive);
                    $fileName = basename($filePath);
                    break;
                case DUP_PRO_Package_File_Type::Log:
                    $filePath = $package->getLocalPackageFilePath(DUP_PRO_Package_File_Type::Log);
                    $fileName = basename($filePath);
                    break;
                default:
                    throw new Exception(__("File type not supported.", 'duplicator-pro'));
            }

            if ($filePath == false) {
                throw new Exception(__("File don\'t exists", 'duplicator-pro'));
            }

            \Duplicator\Libs\Snap\SnapIO::serveFileForDownload($filePath, $fileName, DUPLICATOR_PRO_BUFFER_DOWNLOAD_SIZE);
        } catch (Exception $ex) {
            DUP_PRO_Log::trace('Unable to download Package file: ' . $ex->getMessage());
            wp_die(esc_html($ex->getMessage()));
        }
    }

    /**
     * Hook ajax transfer data
     *
     * @return void
     */
    public function detailsTransferGetPackageVM()
    {
        AjaxWrapper::json(
            array(
                __CLASS__,
                'detailsTransferGetPackageVMCallback',
            ),
            'duplicator_pro_packages_details_transfer_get_package_vm',
            SnapUtil::sanitizeTextInput(SnapUtil::INPUT_REQUEST, 'nonce'),
            CapMng::CAP_CREATE
        );
    }

    /**
     * Hook ajax handler for packages_details_transfer_get_package_vm
     * Retrieve view model for the Packages/Details/Transfer screen
     * active_package_id: true/false
     * percent_text: Percent through the current transfer
     * text: Text to display
     * transfer_logs: array of transfer request vms (start, stop, status, message)
     *
     * @return array<string, mixed>
     */
    public static function detailsTransferGetPackageVMCallback()
    {
        $inputData = filter_input_array(INPUT_POST, array(
            'package_id' => array(
                'filter'  => FILTER_VALIDATE_INT,
                'flags'   => FILTER_REQUIRE_SCALAR,
                'options' => array('default' => false),
            ),
        ));

        $package_id = $inputData['package_id'];
        if (!$package_id) {
            throw new Exception(__("Invalid request.", 'duplicator-pro'));
        }

        if (!CapMng::can(CapMng::CAP_STORAGE, false)) {
            throw new Exception('Security issue.');
        }

        $package = DUP_PRO_Package::get_by_id($package_id);
        if (!$package) {
            $msg = sprintf(__('Could not get package by ID %s', 'duplicator-pro'), $package_id);
            throw new Exception($msg);
        }

        $vm = new stdClass();

        /* -- First populate the transfer log information -- */

        // If this is the package being requested include the transfer details
        $vm->transfer_logs = array();

        $active_upload_info = null;

        $storages = AbstractStorageEntity::getAll();

        foreach ($package->upload_infos as &$upload_info) {
            if ($upload_info->getStorageId() === StoragesUtil::getDefaultStorageId()) {
                continue;
            }

            $status      = $upload_info->get_status();
            $status_text = $upload_info->get_status_text();

            $transfer_log = new stdClass();

            if ($upload_info->get_started_timestamp() == null) {
                $transfer_log->started = __('N/A', 'duplicator-pro');
            } else {
                $transfer_log->started = DUP_PRO_DATE::getLocalTimeFromGMTTicks($upload_info->get_started_timestamp());
            }

            if ($upload_info->get_stopped_timestamp() == null) {
                $transfer_log->stopped = __('N/A', 'duplicator-pro');
            } else {
                $transfer_log->stopped = DUP_PRO_DATE::getLocalTimeFromGMTTicks($upload_info->get_stopped_timestamp());
            }

            $transfer_log->status_text = $status_text;
            $transfer_log->message     = $upload_info->get_status_message();

            $transfer_log->storage_type_text = __('Unknown', 'duplicator-pro');
            foreach ($storages as $storage) {
                if ($storage->getId() == $upload_info->getStorageId()) {
                    $transfer_log->storage_type_text = $storage->getStypeName();
                    // break;
                }
            }

            array_unshift($vm->transfer_logs, $transfer_log);

            if ($status == DUP_PRO_Upload_Status::Running) {
                if ($active_upload_info != null) {
                    DUP_PRO_Log::trace("More than one upload info is running at the same time for package {$package->ID}");
                }

                $active_upload_info = &$upload_info;
            }
        }

        /* -- Now populate the activa package information -- */
        $active_package = DUP_PRO_Package::get_next_active_package();

        if ($active_package == null) {
            // No active package
            $vm->active_package_id = -1;
            $vm->text              = __('No package is building.', 'duplicator-pro');
        } else {
            $vm->active_package_id = $active_package->ID;

            if ($active_package->ID == $package_id) {
                if ($active_upload_info != null) {
                    $vm->percent_text = "{$active_upload_info->progress}%";
                    $vm->text         = $active_upload_info->get_status_message();
                } else {
                    // We see this condition at the beginning and end of the transfer so throw up a generic message
                    $vm->percent_text = "";
                    $vm->text         = __("Synchronizing with server...", 'duplicator-pro');
                }
            } else {
                $vm->text = __("Another package is presently running.", 'duplicator-pro');
            }

            if ($active_package->is_cancel_pending()) {
                // If it's getting cancelled override the normal text
                $vm->text = __("Cancellation pending...", 'duplicator-pro');
            }
        }

        return [
            'success' => true,
            'vm'      => $vm,
        ];
    }

    /**
     * Hook ajax manual transfer storage
     *
     * @return void
     */
    public function manualTransferStorage()
    {
        AjaxWrapper::json(
            array(
                __CLASS__,
                'manualTransferStorageCallback',
            ),
            'duplicator_pro_manual_transfer_storage',
            SnapUtil::sanitizeTextInput(INPUT_POST, 'nonce'),
            CapMng::CAP_CREATE
        );
    }

    /**
     * Manual transfer storage callback
     *
     * @return array<string, mixed>
     */
    public static function manualTransferStorageCallback()
    {
        $isValid   = true;
        $inputData = filter_input_array(INPUT_POST, array(
            'package_id'  => array(
                'filter'  => FILTER_VALIDATE_INT,
                'flags'   => FILTER_REQUIRE_SCALAR,
                'options' => array('default' => false),
            ),
            'storage_ids' => array(
                'filter'  => FILTER_VALIDATE_INT,
                'flags'   => FILTER_REQUIRE_ARRAY,
                'options' => array('default' => false),
            ),
            'download'    => array(
                'filter'  => FILTER_VALIDATE_BOOLEAN,
                'flags'   => FILTER_REQUIRE_SCALAR,
                'options' => array('default' => false),
            ),
        ));

        $package_id  = $inputData['package_id'];
        $storage_ids = $inputData['storage_ids'];
        $isDownload  = $inputData['download'];
        $isValid     = $package_id !== false && $storage_ids !== false;

        try {
            if (!$isValid) {
                throw new Exception(__("Invalid request.", 'duplicator-pro'));
            }

            DUP_PRO_Log::trace("Test if package is Running");
            if (DUP_PRO_Package::isPackageRunning()) {
                DUP_PRO_Log::trace("Package running.");
                $msg = sprintf(__('Trying to queue a transfer for package %d but a package is already active!', 'duplicator-pro'), $package_id);
                throw new Exception($msg);
            } else {
                DUP_PRO_Log::trace("Package not running.");
            }

            $package = DUP_PRO_Package::get_by_id($package_id);
            DUP_PRO_Log::open($package->getNameHash());

            if (!$package) {
                throw new Exception(sprintf(esc_html__('Could not find package ID %d!', 'duplicator-pro'), $package_id));
            }

            if (empty($storage_ids)) {
                throw new Exception("Please select a storage.");
            }

            $info  = "\n";
            $info .= "********************************************************************************\n";
            $info .= "********************************************************************************\n";
            $info .= "PACKAGE MANUAL TRANSFER REQUESTED: " . @date("Y-m-d H:i:s") . "\n";
            $info .= "********************************************************************************\n";
            $info .= "********************************************************************************\n\n";
            DUP_PRO_Log::infoTrace($info);

            foreach ($storage_ids as $storage_id) {
                if (($storage = AbstractStorageEntity::getById($storage_id)) === false) {
                    throw new Exception(sprintf(__('Could not find storage ID %d!', 'duplicator-pro'), $storage_id));
                }

                DUP_PRO_Log::infoTrace(
                    'Storage adding to the package "' . $package->getName() .
                    ' [Package Id: ' . $package_id . ']":: Storage Id: "' . $storage_id .
                    '" Storage Name: "' . esc_html($storage->getName()) .
                    '" Storage Type: "' . esc_html($storage->getStypeName()) . '"'
                );

                $upload_info = new DUP_PRO_Package_Upload_Info($storage_id);
                $upload_info->setDownloadFromRemote($isDownload);
                array_push($package->upload_infos, $upload_info);
            }

            $package->set_status(DUP_PRO_PackageStatus::STORAGE_PROCESSING);
            $package->timer_start = DUP_PRO_U::getMicrotime();

            $package->update();
        } catch (Exception $ex) {
            DUP_PRO_Log::trace($ex->getMessage());
            throw $ex;
        }

        return ['success' => true];
    }

    /**
     * Hook ajax wp_ajax_duplicator_pro_get_folder_children
     *
     * @return never
     */
    public function getFolderChildren()
    {
        DUP_PRO_Handler::init_error_handler();
        check_ajax_referer('duplicator_pro_get_folder_children', 'nonce');

        $json      = array();
        $isValid   = true;
        $inputData = filter_input_array(INPUT_GET, array(
            'folder'  => array(
                'filter'  => FILTER_SANITIZE_SPECIAL_CHARS,
                'flags'   => FILTER_REQUIRE_SCALAR,
                'options' => array('default' => false),
            ),
            'exclude' => array(
                'filter'  => FILTER_SANITIZE_SPECIAL_CHARS,
                'flags'   => FILTER_REQUIRE_ARRAY,
                'options' => array(
                    'default' => array(),
                ),
            ),
        ));
        $folder    = $inputData['folder'];
        $exclude   = $inputData['exclude'];

        if ($folder === false) {
            $isValid = false;
        }

        ob_start();
        try {
            CapMng::can(CapMng::CAP_BASIC);

            if (!$isValid) {
                throw new Exception(__('Invalid request.', 'duplicator-pro'));
            }
            if (is_dir($folder)) {
                try {
                    $Package = DUP_PRO_Package::get_temporary_package();
                } catch (Exception $e) {
                    $Package = null;
                }

                $treeObj = new DUP_PRO_Tree_files($folder, true, $exclude);
                $treeObj->uasort(array('DUP_PRO_Archive', 'sortTreeByFolderWarningName'));
                if (!is_null($Package)) {
                    $treeObj->treeTraverseCallback(array($Package->Archive, 'checkTreeNodesFolder'));
                }

                $jsTreeData = DUP_PRO_Archive::getJsTreeStructure($treeObj, '', false);
                $json       = $jsTreeData['children'];
            }
        } catch (Exception $e) {
            DUP_PRO_Log::trace($e->getMessage());
            $json['message'] = $e->getMessage();
        }
        ob_clean();
        wp_send_json($json);
    }

    /**
     * Show remote storage options from where the package can be downloaded
     *
     * @return void
     */
    public function remoteRestoreDownloadOptions()
    {
        AjaxWrapper::json(
            array(
                __CLASS__,
                'remoteRestoreDownloadOptionsCallback',
            ),
            'duplicator_get_remote_restore_download_options',
            SnapUtil::sanitizeTextInput(INPUT_POST, 'nonce'),
            [
                CapMng::CAP_BACKUP_RESTORE,
                CapMng::CAP_STORAGE,
            ]
        );
    }

    /**
     * Show remote storage options from where the package can be downloaded
     *
     * @return array<string,mixed>
     */
    public static function remoteRestoreDownloadOptionsCallback()
    {
        $result = [
            'success'       => true,
            'alreadyInUse'  => false,
            'cancelNeeded'  => false,
            'packageExists' => true,
            'message'       => '',
            'content'       => '',
        ];
        try {
            $packageId = SnapUtil::sanitizeIntInput(SnapUtil::INPUT_REQUEST, 'packageId', -1);

            switch (SnapUtil::sanitizeStrictInput(SnapUtil::INPUT_REQUEST, 'remoteAction')) {
                case 'download':
                    $action = 'download';
                    break;
                case 'restore':
                    $action = 'restore';
                    break;
                default:
                    throw new Exception(__('Invalid action.', 'duplicator-pro'));
            }

            if ($packageId < 0 || ($package = DUP_PRO_Package::get_by_id($packageId)) === false) {
                throw new Exception(__('Invalid package ID.', 'duplicator-pro'));
            }

            if ($package->haveLocalStorage()) {
                throw new Exception(__('Package already exists locally.', 'duplicator-pro'));
            }

            if (DUP_PRO_Package::isPackageRunning()) {
                $result['cancelNeeded'] = true;
                $activePackage          = DUP_PRO_Package::get_next_active_package();

                if ($activePackage !== null && $packageId === $activePackage->ID) {
                    $result['alreadyInUse'] = true;
                }

                return $result;
            }

            $storages = $package->getRemoteStorages();

            if (count($storages) === 0) {
                $result['packageExists'] = false;
                $result['message']       = __('Package does not exist in any remote storage.', 'duplicator-pro');
                return $result;
            }

            if ($action === 'restore') {
                $template = 'admin_pages/packages/remote_download/remote_restore_options';
            } else {
                $template = 'admin_pages/packages/remote_download/remote_download_options';
            }

            $result['content'] = TplMng::getInstance()->render($template, [
                'packageId'     => $package->ID,
                'packageName'   => $package->getName(),
                'isStorageFull' => StoragesUtil::getDefaultStorage()->isFull(),
                'storages'      => $storages,
            ], false);
        } catch (Exception $ex) {
            DUP_PRO_Log::trace($ex->getMessage());
            throw $ex;
        }

        return $result;
    }
}

<?php

namespace Plugins\admin\Controllers;

use Core\LowCode\LowCode;
use Exception;
use JetBrains\PhpStorm\NoReturn;
use Plugins\admin\managers\BranchManager;
use Plugins\admin\managers\SchoolManager;
use Plugins\admin\managers\UserManager;
use Plugins\admin\Models\AcademicYearModel;
use Plugins\admin\Models\ClassesModel;
use Plugins\admin\Models\DivisionModel;
use Plugins\admin\Models\EducationalBoardModel;
use Plugins\admin\Models\SubjectModel;
use Plugins\admin\Models\UnitModel;
use System\FormException;
use System\Request;
use System\Response;

class BranchController extends AdminController
{
    public function __construct()
    {
        parent::__construct();
    }

    #[NoReturn] public function index(Request $request, Response $response): void
    {
        $response->page->slug = $request->getPath();
        $response->page->title = "Branches";
        try {

            LowCode::set('breadcrumb',array(['href'=>site_url('admin'),'text'=>'Dashboard'],['active'=>'active','text'=>'Branch List']));
            $response->add('pagetop','preloader');
            $response->add('pagetop','notifications');
            $response->add('topnav','topnav');
            $response->add('sidebar','sidebar');
            $response->add('content','branch/index');
        }
        catch (Exception $e) {
            echo $e->getMessage();
        }
        $response->render();
    }

    #[NoReturn] public function branch(Request $request, Response $response): void
    {
        $id = $request->getPath(2);
        if($id>0){
            $response->page->slug = $request->getPath();
            try {
                $branch = BranchManager::getBranchById($id);
                $response->page->title = "Branch {$branch['branch_name']}";
                LowCode::set('breadcrumb',array(['href'=>site_url('admin/branches'),'text'=>'Branch List'],['active'=>'active','text'=>'Branch']));
                LowCode::set('branch_name',$branch['branch_name']);
                $response->add('pagetop','preloader');
                $response->add('pagetop','notifications');
                $response->add('topnav','topnav');
                $response->add('sidebar','sidebar');
                $response->add('content','branch/single');
                $response->render();
            } catch (Exception $e) {
                $response->show_error($e->getMessage());
            }
        }
        else{
            $response->not_found();
        }
    }


    #[NoReturn] public function create(Request $request, Response $response): void
    {
        $response->page->slug = $request->getPath();
        $response->page->title = "Create New Branch";
        try {
            $response->add('pagetop','preloader');
            $response->add('pagetop','notifications');
            $response->add('topnav','topnav');
            $response->add('sidebar','sidebar');
            $roles= UserManager::getRoles();
            $response->add('content','branch/create',array('roles'=>$roles));
        }
        catch (Exception $e) {
            echo $e->getMessage();
        }
        $response->render();
    }

    #[NoReturn] public function branch_create(Request $request, Response $response): void
    {
        $errors = array();
        $message = "";
        try{
            $branchName = $request->input('branch_name');
            $status= $request->input('branch_status');
            $location= $request->input('location');
            $address= $request->input('address');
            $city= $request->input('city');
            $pin= $request->input('pin');
            $contact= $request->input('contact_no');
            $email= $request->input('email');
            $schoolID= 1;
            $logo= $request->inputFileBlob('logo');

            // Check the size of the uploaded Blob file
            if ($logo !== null) {
                $maxSize = 200 * 1024; // 200 KB in bytes
                if (strlen($logo) > $maxSize) {
                     $errors['logo'] = "Uploaded file exceeds the maximum allowed size of 200 KB.";
                }
            }
            else{
                BranchManager::createBranch($branchName, $status, $location, $address, $city, $pin, $contact, $email, null, $logo);
                $message = "Branch Created Successfully...";

            }
        } catch (FormException $e){
            $errors['error'] =  $e->getMessage();
        } catch (Exception $e){
            $errors['db_error'] = $e->getMessage();
        }
        $response->json(array('status'=>false,'errors'=>$errors,'message'=>$message));
    }

    #[NoReturn] public function branch_table(Request $request, Response $response): void{

        try {
            $columns = ['ID'=>'id','name'=>'branch_name','status'=>'status','location'=>'location','address'=>'address','city'=>'city','pin'=>'pincode','phone'=>'contact','email'=>'email','stamp'=>'created'];
            $response->dataTable($request,BranchManager::BranchTable(),$columns);
        }
        catch (\Exception $e) {
            echo $e->getMessage();
        }
    }


}
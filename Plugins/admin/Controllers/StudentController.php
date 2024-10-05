<?php

namespace Plugins\admin\Controllers;

use Core\LowCode\LowCode;
use Exception;
use JetBrains\PhpStorm\NoReturn;
use Plugins\admin\managers\BranchManager;
use Plugins\admin\managers\SchoolManager;
use Plugins\admin\managers\UserManager;
use System\FormException;
use System\Request;
use System\Response;

class StudentController extends AdminController
{
    public function __construct()
    {
        parent::__construct();
    }

    #[NoReturn] public function index(Request $request, Response $response): void
    {
        $response->page->slug = $request->getPath();
        $response->page->title = "Students";
        try {
            LowCode::set('breadcrumb',array(['href'=>site_url('admin'),'text'=>'Dashboard'],['active'=>'active','text'=>'Student List']));
            $response->add('pagetop','preloader');
            $response->add('pagetop','notifications');
            $response->add('topnav','topnav');
            $response->add('sidebar','sidebar');
            $response->add('content','student/index');
        }
        catch (Exception $e) {
            echo $e->getMessage();
        }
        $response->render();
    }

    #[NoReturn] public function single(Request $request, Response $response): void
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
            LowCode::set('branch_select_list',BranchManager::getBranchSelectList());
            LowCode::set('class_select_list',SchoolManager::getClassSelectList());
            LowCode::set('division_select_list',SchoolManager::getDivisionSelectList());
            LowCode::set('year_select_list',SchoolManager::getAcademicYearSelectList());
            LowCode::set('breadcrumb',array(['href'=>site_url('admin'),'text'=>'Dashboard'],['href'=>site_url('admin/students'),'text'=>'Students'],['active'=>'active','text'=>'create']));
            $roles= UserManager::getRoles();

            $response->add('pagetop','preloader');
            $response->add('pagetop','notifications');
            $response->add('topnav','topnav');
            $response->add('sidebar','sidebar');
            $response->add('content','student/create',array('roles'=>$roles));
        }
        catch (Exception $e) {
            echo $e->getMessage();
        }
        $response->render();
    }

    #[NoReturn] public function save(Request $request, Response $response): void
    {
        $errors = array();
        $message = "";
        try{
            $studentId = $request->input('student_id');
            $status= $request->input('status');
            $year= $request->input('year');

            $branchId= $request->input('branch');
            $division= $request->input('division');
            $class= $request->input('class');
            $classroom= $request->input('classroom');
            $roll_no= $request->input('roll_no');

            $username= $request->input('username');
            $studentEmail= $request->input('student_email');

            $firstName= $request->input('first_name');
            $middleName= $request->input('middle_name');
            $lastName= $request->input('last_name');
            $birthDate= $request->input('birth_date');

            $fatherName= $request->input('father_name');
            $motherName= $request->input('mother_name');
            $fatherEmail= $request->input('father_email');
            $motherEmail= $request->input('mother_email');
            $fatherMobile= $request->input('father_mobile');
            $motherMobile= $request->input('mother_mobile');

            $address= $request->input('address');
            $location= $request->input('location');
            $pin= $request->input('pin');

            $schoolId= 1;

            BranchManager::createStudent($studentId, $firstName, $lastName, $username, $studentEmail,
                $schoolId, $year,$branchId, $division, $class, $status,
                $motherName, $fatherName, $fatherEmail, $fatherMobile,
                $motherEmail, $motherMobile, $address, $location, $birthDate,$middleName,$classroom,$roll_no,$pin);
            $message = "Created Successfully...";

        } catch (FormException $e){
            $errors['error'] =  $e->getMessage();
        } catch (Exception $e){
            $errors['db_error'] = $e->getMessage();
        }
        $response->json(array('status'=>false,'errors'=>$errors,'message'=>$message));
    }

    #[NoReturn] public function table(Request $request, Response $response): void{

        try {
            $columns = ['ID'=>'id','name'=>'branch_name','status'=>'status','location'=>'location','address'=>'address','city'=>'city','pin'=>'pincode','phone'=>'contact','email'=>'email','stamp'=>'created'];
            $response->dataTable($request,BranchManager::BranchTable(),$columns);
        }
        catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

}
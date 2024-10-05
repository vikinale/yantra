<?php

namespace Plugins\admin\Controllers;

use Core\LowCode\LowCode;
use Exception;
use JetBrains\PhpStorm\NoReturn;
use Plugins\admin\managers\BranchManager;
use Plugins\admin\managers\SchoolManager;
use System\FormException;
use System\Request;
use System\Response;

class StudentGroupController extends AdminController
{
    public function __construct()
    {
        parent::__construct();
    }

    #[NoReturn] public function index(Request $request, Response $response): void
    {
        $response->page->slug = $request->getPath();
        $response->page->title = "Student Group";
        try {
            $id = $request->getPath(2);

            $rec = null;
            if($id>0){
                $rec = BranchManager::getStudentGroup($id);
            }
            LowCode::set('branch_select_list',BranchManager::getBranchSelectList());
            LowCode::set('year_select_list',SchoolManager::getAcademicYearSelectList());
            LowCode::set('breadcrumb',array(['href'=>site_url('admin'),'text'=>'Dashboard'],['active'=>'active','text'=>'Classrooms ']));
            $response->add('pagetop','preloader');
            $response->add('pagetop','notifications');
            $response->add('topnav','topnav');
            $response->add('sidebar','sidebar');
            $response->add('content','group/index',['rec'=>$rec]);
        }
        catch (Exception $e) {
            echo $e->getMessage();
        }
        $response->render();
    }

    #[NoReturn] public function create(Request $request, Response $response): void
    {
        $errors = array();
        $message = "";
        try{
            $rec_id = $request->input('rec_id');
            $branch = $request->input('branch');
            $group_name= $request->input('group_name');
            $year= $request->input('year_id');

            if($rec_id>0){
                BranchManager::updateStudentGroup($rec_id,$group_name,$branch,$year);
            }
            else{
                BranchManager::createStudentGroup($group_name,$branch,$year);
            }
            $message = "Successfully...";

        } catch (FormException $e){
            $errors['error'] =  $e->getMessage();
        } catch (Exception $e){
            $errors['db_error'] = $e->getMessage();
        }
        $response->json(array('status'=>(count($errors)==0),'errors'=>$errors,'message'=>$message));
    }

    #[NoReturn] public function table(Request $request, Response $response): void{

        try {
            $columns = ['id'=>'ID','name'=>'group_name','branch'=>'b.branch_name','year_name'=>'a.year_name'];
            $response->dataTable($request,BranchManager::StudentGroupTable(),$columns);
        }
        catch (\Exception $e) {
            echo $e->getMessage();
        }
    }
}
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

class ClassroomController extends AdminController
{
    public function __construct()
    {
        parent::__construct();
    }

    #[NoReturn] public function index(Request $request, Response $response): void
    {
        $response->page->slug = $request->getPath();
        $response->page->title = "Classrooms";
        try {
            $id = $request->getPath(2);
            $rec = null;
            if($id>0){
                $rec = BranchManager::getClassroom($id);
            }
            LowCode::set('branch_select_list',BranchManager::getBranchSelectList());
            LowCode::set('class_select_list',SchoolManager::getClassSelectList());
            LowCode::set('division_select_list',SchoolManager::getDivisionSelectList());
            LowCode::set('year_select_list',SchoolManager::getAcademicYearSelectList());
            LowCode::set('breadcrumb',array(['href'=>site_url('admin'),'text'=>'Dashboard'],['active'=>'active','text'=>'Classrooms ']));
            $response->add('pagetop','preloader');
            $response->add('pagetop','notifications');
            $response->add('topnav','topnav');
            $response->add('sidebar','sidebar');
            $response->add('content','classroom/index',['rec'=>$rec]);
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
            $class = $request->input('class');
            $division =  $request->input('division');
            $year= $request->input('year');

            $b = BranchManager::getBranchById($branch);
            $c = SchoolManager::getClassByID($class);
            $d = SchoolManager::getDivisionByID($division);
            $y = SchoolManager::getAcademicYearByID($year);

            $classroom_name = "{$b['branch_name']}{$c['class_name']}{$d['division_name']}{$y['year_name']}";

            if($rec_id>0){
                BranchManager::updateClassroom($rec_id,$branch,$class,$division,$year,$classroom_name);
            }
            else{
                BranchManager::createClassroom($branch,$class,$division,$year,$classroom_name);
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
            $columns = ['id'=>'classroom.ID','shortname'=>'shortname','branch'=>'b.branch_name','class'=>'c.class_name','division'=>'d.division_name', 'year_name'=>'a.year_name'];
            $response->dataTable($request,BranchManager::ClassroomTable(),$columns);
        }
        catch (\Exception $e) {
            echo $e->getMessage();
        }
    }
}
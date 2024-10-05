<?php

namespace Plugins\admin\Controllers;

use Core\Controllers\BaseController;
use Core\LowCode\LowCode;
use Exception;
use JetBrains\PhpStorm\NoReturn;
use Plugins\admin\managers\SchoolManager;
use Plugins\admin\Models\AcademicYearModel;
use Plugins\admin\Models\ClassesModel;
use Plugins\admin\Models\DivisionModel;
use Plugins\admin\Models\EducationalBoardModel;
use Plugins\admin\Models\SubjectModel;
use Plugins\admin\Models\UnitModel;
use System\FormException;
use System\Request;
use System\Response;

class SchoolController extends AdminController
{
    public function __construct()
    {
        parent::__construct();
    }

    #[NoReturn] public function index(Request $request, Response $response): void
    {
        $response->page->slug = $request->getPath();
        $response->page->title = "School";
        try {
            $response->add('pagetop','preloader');
            $response->add('pagetop','notifications');
            $response->add('topnav','topnav');
            $response->add('sidebar','sidebar');
            $response->add('content','school/index');
        }
        catch (Exception $e) {
            echo $e->getMessage();
        }
        $response->render();
    }

    #[NoReturn] public function create(Request $request, Response $response): void
    {

    }

    #[NoReturn] public function academic_years(Request $request, Response $response): void
    {
        $response->page->slug = $request->getPath();
        $response->page->title = "Academic Years";
        try {
            LowCode::set('breadcrumb',array(['href'=>site_url(),'text'=>'Dashboard'],['active'=>'active','text'=>'Academic Year']));

            $response->add('pagetop','preloader');
            $response->add('pagetop','notifications');
            $response->add('topnav','topnav');
            $response->add('sidebar','sidebar');
            $response->add('content','school/academic-years');
        }
        catch (Exception $e) {
            echo $e->getMessage();
        }
        $response->render();
    }
    #[NoReturn] public function academic_year_create(Request $request, Response $response): void
    {
        $errors = array();
        $message = "";
        try{
            $id = $request->input('year_id',0);
            $year_name = $request->input('year_name',null);
            $start_date = $request->input('start_date',null);
            $end_date = $request->input('end_date',null);

            $start_date = $this->toMysqlDate($start_date);
            $end_date = $this->toMysqlDate($end_date);

            if($id>0){
                SchoolManager::updateAcademicYear($id, array('start_date'=>$start_date,'end_date'=>$end_date));
                $message = "Academic Year Updated Successfully...";
            }
            else{
                SchoolManager::createAcademicYear($year_name,$start_date,$end_date);
                $message = "Academic Year Created Successfully...";
            }
        } catch (FormException $e){
            $errors['error'] =  $e->getMessage();
        } catch (Exception $e){
            $errors['db_error'] = $e->getMessage();
        }
        $response->json(array('status'=>(count($errors)==0),'errors'=>$errors,'message'=>$message));
    }
    #[NoReturn] public function academic_year_update(Request $request, Response $response): void {
        $response->page->slug = $request->getPath();
        $response->page->title = "Academic Years";
        try {
            $response->add('pagetop','preloader');
            $response->add('pagetop','notifications');
            $response->add('topnav','topnav');
            $response->add('sidebar','sidebar');

            $id = $request->getPath(3);
            $id = $id?(int)$id:-1;
            if($id > 0){
                $record =  SchoolManager::getAcademicYearByID($id);
                $record['start_date'] = $this->fromMysqlDate($record['start_date'],"d M, Y");
                $record['end_date'] = $this->fromMysqlDate($record['end_date'],"d M, Y");
                $response->add('content','school/academic-years',['record'=>$record]);
            }
            else{
                $response->add('content','school/academic-years');
            }
        }
        catch (Exception $e) {
            echo $e->getMessage();
        }
        $response->render();
    }
    #[NoReturn] public function academic_year_table(Request $request, Response $response): void{
        $m = new AcademicYearModel();
        try {
            $columns =['ID'=>'id','name'=>'year_name','start'=>'start_date',"end"=> "end_date","school"=> "school_id"];
            $response->dataTable($request,$m->select("id as ID, year_name as name, DATE_FORMAT(start_date, '%d %b, %Y') as start, DATE_FORMAT(end_date, '%d %b, %Y') as end"),$columns);
        }
        catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    #[NoReturn] public function educational_boards(Request $request, Response $response): void
    {
        $response->page->slug = $request->getPath();
        $response->page->title = "Educational Board";
        try {
            $response->add('pagetop','preloader');
            $response->add('pagetop','notifications');
            $response->add('topnav','topnav');
            $response->add('sidebar','sidebar');
            $response->add('content','school/educational-boards');
        }
        catch (Exception $e) {
            echo $e->getMessage();
        }
        $response->render();
    }
    #[NoReturn] public function educational_boards_create(Request $request, Response $response): void
    {
        $errors = array();
        $message = "";
        try{
            $id = $request->input('row_id',0);
            $board_name = $request->input('board_name',null);
            $short_code = $request->input('short_code',null);

            if($id>0){
                SchoolManager::updateEducationalBoard($id, array('board_name'=>$board_name,'code'=>$short_code));
                $message = "Academic Year Updated Successfully...";
            }
            else{
                SchoolManager::createEducationalBoard($board_name,$short_code);
                $message = "Academic Year Created Successfully...";
            }
        } catch (FormException $e){
            $errors['error'] =  $e->getMessage();
        } catch (Exception $e){
            $errors['db_error'] = $e->getMessage();
        }
        $response->json(array('status'=>false,'errors'=>$errors,'message'=>$message));
    }
    #[NoReturn] public function educational_boards_update(Request $request, Response $response): void {
        $response->page->slug = $request->getPath();
        $response->page->title = "Educational Board";
        try {
            $response->add('pagetop','preloader');
            $response->add('pagetop','notifications');
            $response->add('topnav','topnav');
            $response->add('sidebar','sidebar');

            $id = $request->getPath(3);
            $id = $id?(int)$id:-1;
            if($id > 0){
                $record =  SchoolManager::getEducationalBoardByID($id);
                $response->add('content','school/educational-boards',['record'=>$record]);
            }
            else{
                $response->add('content','school/educational-boards');
            }
        }
        catch (Exception $e) {
            echo $e->getMessage();
        }
        $response->render();
    }
    #[NoReturn] public function educational_boards_table(Request $request, Response $response): void{
        $m = new EducationalBoardModel();
        try {
            $columns =['ID'=>'id','name'=>'board_name','code'=>'code'];
            $response->dataTable($request,$m->select("id as ID, board_name as name, code"),$columns);
        }
        catch (\Exception $e) {
            echo $e->getMessage();
        }
    }


    #[NoReturn] public function classes(Request $request, Response $response): void
    {
        $response->page->slug = $request->getPath();
        $response->page->title = "Educational Board";
        try {
            $response->add('pagetop','preloader');
            $response->add('pagetop','notifications');
            $response->add('topnav','topnav');
            $response->add('sidebar','sidebar');
            $response->add('content','school/classes');
        }
        catch (Exception $e) {
            echo $e->getMessage();
        }
        $response->render();
    }
    #[NoReturn] public function classes_create(Request $request, Response $response): void
    {
        $errors = array();
        $message = "";
        try{
            $id = $request->input('row_id',0);
            $class_name = $request->input('class_name',null);
            $short_code = $request->input('short_code',null);

            if($id>0){
                SchoolManager::updateClass($id, array('class_name'=>$class_name,'code'=>$short_code));
                $message = "Academic Year Updated Successfully...";
            }
            else{
                SchoolManager::createClass($class_name,$short_code);
                $message = "Academic Year Created Successfully...";
            }
        } catch (FormException $e){
            $errors['error'] =  $e->getMessage();
        } catch (Exception $e){
            $errors['db_error'] = $e->getMessage();
        }
        $response->json(array('status'=>false,'errors'=>$errors,'message'=>$message));
    }
    #[NoReturn] public function classes_update(Request $request, Response $response): void {
        $response->page->slug = $request->getPath();
        $response->page->title = "Educational Board";
        try {
            $response->add('pagetop','preloader');
            $response->add('pagetop','notifications');
            $response->add('topnav','topnav');
            $response->add('sidebar','sidebar');

            $id = $request->getPath(3);
            $id = $id?(int)$id:-1;
            if($id > 0){
                $record =  SchoolManager::getClassByID($id);
                $response->add('content','school/classes',['record'=>$record]);
            }
            else{
                $response->add('content','school/classes');
            }
        }
        catch (Exception $e) {
            echo $e->getMessage();
        }
        $response->render();
    }
    #[NoReturn] public function classes_table(Request $request, Response $response): void{
        $m = new ClassesModel();
        try {
            $columns =['ID'=>'id','name'=>'class_name','code'=>'code'];
            $response->dataTable($request,$m->select("id as ID, class_name as name, code"),$columns);
        }
        catch (\Exception $e) {
            echo $e->getMessage();
        }
    }


    #[NoReturn] public function divisions(Request $request, Response $response): void
    {
        $response->page->slug = $request->getPath();
        $response->page->title = "Educational Board";
        try {
            $response->add('pagetop','preloader');
            $response->add('pagetop','notifications');
            $response->add('topnav','topnav');
            $response->add('sidebar','sidebar');
            $response->add('content','school/divisions');
        }
        catch (Exception $e) {
            echo $e->getMessage();
        }
        $response->render();
    }
    #[NoReturn] public function divisions_create(Request $request, Response $response): void
    {
        $errors = array();
        $message = "";
        try{
            $id = $request->input('row_id',0);
            $division_name = $request->input('division_name',null);
            $short_code = $request->input('short_code',null);

            if($id>0){
                SchoolManager::updateDivision($id, array('division_name'=>$division_name,'code'=>$short_code));
                $message = "Academic Year Updated Successfully...";
            }
            else{
                SchoolManager::createDivision($division_name,$short_code);
                $message = "Academic Year Created Successfully...";
            }
        } catch (FormException $e){
            $errors['error'] =  $e->getMessage();
        } catch (Exception $e){
            $errors['db_error'] = $e->getMessage();
        }
        $response->json(array('status'=>false,'errors'=>$errors,'message'=>$message));
    }
    #[NoReturn] public function divisions_update(Request $request, Response $response): void {
        $response->page->slug = $request->getPath();
        $response->page->title = "Educational Board";
        try {
            $response->add('pagetop','preloader');
            $response->add('pagetop','notifications');
            $response->add('topnav','topnav');
            $response->add('sidebar','sidebar');

            $id = $request->getPath(3);
            $id = $id?(int)$id:-1;
            if($id > 0){
                $record =  SchoolManager::getDivisionByID($id);
                $response->add('content','school/divisions',['record'=>$record]);
            }
            else{
                $response->add('content','school/divisions');
            }
        }
        catch (Exception $e) {
            echo $e->getMessage();
        }
        $response->render();
    }
    #[NoReturn] public function divisions_table(Request $request, Response $response): void{
        $m = new DivisionModel();
        try {
            $columns =['ID'=>'id','name'=>'division_name','code'=>'code'];
            $response->dataTable($request,$m->select("id as ID, division_name as name, code"),$columns);
        }
        catch (\Exception $e) {
            echo $e->getMessage();
        }
    }

    #[NoReturn] public function subjects(Request $request, Response $response): void
    {
        $response->page->slug = $request->getPath();
        $response->page->title = "Educational Board";
        try {
            $response->add('pagetop','preloader');
            $response->add('pagetop','notifications');
            $response->add('topnav','topnav');
            $response->add('sidebar','sidebar');
            $response->add('content','school/subjects');
        }
        catch (Exception $e) {
            echo $e->getMessage();
        }
        $response->render();
    }
    #[NoReturn] public function subjects_create(Request $request, Response $response): void
    {
        $errors = array();
        $message = "";
        try{
            $id = $request->input('row_id',0);
            $division_name = $request->input('subject_name',null);
            $short_code = $request->input('short_code',null);

            if($id>0){
                SchoolManager::updateSubject($id, array('subject_name'=>$division_name,'code'=>$short_code));
                $message = "Academic Year Updated Successfully...";
            }
            else{
                SchoolManager::createSubject($division_name,$short_code);
                $message = "Academic Year Created Successfully...";
            }
        } catch (FormException $e){
            $errors['error'] =  $e->getMessage();
        } catch (Exception $e){
            $errors['db_error'] = $e->getMessage();
        }
        $response->json(array('status'=>false,'errors'=>$errors,'message'=>$message));
    }
    #[NoReturn] public function subjects_update(Request $request, Response $response): void {
        $response->page->slug = $request->getPath();
        $response->page->title = "Educational Board";
        try {
            $response->add('pagetop','preloader');
            $response->add('pagetop','notifications');
            $response->add('topnav','topnav');
            $response->add('sidebar','sidebar');

            $id = $request->getPath(3);
            $id = $id?(int)$id:-1;
            if($id > 0){
                $record =  SchoolManager::getSubjectByID($id);
                $response->add('content','school/subjects',['record'=>$record]);
            }
            else{
                $response->add('content','school/subjects');
            }
        }
        catch (Exception $e) {
            echo $e->getMessage();
        }
        $response->render();
    }
    #[NoReturn] public function subjects_table(Request $request, Response $response): void{
        $m = new SubjectModel();
        try {
            $columns =['ID'=>'id','name'=>'subject_name','code'=>'code'];
            $response->dataTable($request,$m->select("id as ID, subject_name as name, code"),$columns);
        }
        catch (\Exception $e) {
            echo $e->getMessage();
        }
    }


    #[NoReturn] public function units(Request $request, Response $response): void
    {
        $response->page->slug = $request->getPath();
        $response->page->title = "Units";
        try {
            $response->add('pagetop','preloader');
            $response->add('pagetop','notifications');
            $response->add('topnav','topnav');
            $response->add('sidebar','sidebar');
            $response->add('content','school/units');
        }
        catch (Exception $e) {
            echo $e->getMessage();
        }
        $response->render();
    }
    #[NoReturn] public function units_create(Request $request, Response $response): void
    {
        $errors = array();
        $message = "";
        try{
            $id = $request->input('row_id',0);
            $unit_name = $request->input('unit_name',null);
            $short_code = $request->input('short_code',null);

            if($id>0){
                SchoolManager::updateUnit($id, array('unit_name'=>$unit_name,'code'=>$short_code));
                $message = "Academic Year Updated Successfully...";
            }
            else{
                SchoolManager::createUnit($unit_name,$short_code);
                $message = "Academic Year Created Successfully...";
            }
        } catch (FormException $e){
            $errors['error'] =  $e->getMessage();
        } catch (Exception $e){
            $errors['db_error'] = $e->getMessage();
        }
        $response->json(array('status'=>false,'errors'=>$errors,'message'=>$message));
    }
    #[NoReturn] public function units_update(Request $request, Response $response): void {
        $response->page->slug = $request->getPath();
        $response->page->title = "Educational Board";
        try {
            $response->add('pagetop','preloader');
            $response->add('pagetop','notifications');
            $response->add('topnav','topnav');
            $response->add('sidebar','sidebar');

            $id = $request->getPath(3);
            $id = $id?(int)$id:-1;
            if($id > 0){
                $record =  SchoolManager::getUnitByID($id);
                $response->add('content','school/units',['record'=>$record]);
            }
            else{
                $response->add('content','school/units');
            }
        }
        catch (Exception $e) {
            echo $e->getMessage();
        }
        $response->render();
    }
    #[NoReturn] public function units_table(Request $request, Response $response): void{
        $m = new UnitModel();
        try {
            $columns =['ID'=>'id','name'=>'unit_name','code'=>'code'];
            $response->dataTable($request,$m->select("id as ID, unit_name as name, code"),$columns);
        }
        catch (\Exception $e) {
            echo $e->getMessage();
        }
    }
}
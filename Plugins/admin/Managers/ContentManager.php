<?php
namespace Plugins\admin\Managers;

use Exception;
use Plugins\admin\Models\ContentModels\DescriptiveQuestionVersionModel;
use Plugins\admin\Models\ContentModels\FillInTheBlankModel;
use Plugins\admin\Models\ContentModels\MatchTheColumnsModel;
use Plugins\admin\Models\ContentModels\OptionModel;
use Plugins\admin\Models\ContentModels\QuestionModel;
use System\Database;
use System\FormException;

class ContentManager {

    public static function addQuestion(string $questionText,
                                       string $questionType,
                                       string $correctOptions = null,
                                              $mediaType = null,
                                              $mediaUrl = null,
                                              $textbookId = null,
                                              $chapterId = null,
                                              $sectionId = null,
                                       array $config = array(),
                                       float $marks = null,
                                       string $hint = null,
                                       int $level = 1,
                                       bool $important = false
    ){
        $questionModel = new QuestionModel();
        try {
           if( Database::beginTransaction()){
               $insertID =  $questionModel->create($questionText, $questionType, $correctOptions, $mediaType, $mediaUrl, $textbookId, $chapterId, $sectionId, $marks, $hint, $level, $important);
               if($insertID == 0){
                    throw new Exception("Unable to Insert Question in Database.");
               }
               $x = 1;
               switch ($questionType){
                   case 'Match the Columns':
                       if(!isset($config['sections']))
                           throw new Exception("Match the column sections are note provided ");

                       $m = new MatchTheColumnsModel();
                       foreach ($config['sections'] as $section){
                           $m->create($insertID,$section['left'],$section['right'],$section['marks']);
                       }
                       break;
                   case 'True or False':
                       if(!isset($config['options']))
                           throw new Exception("Match the column options are note provided ");

                       $m = new OptionModel();
                       foreach ($config['options'] as $n=>$option){
                           $m->create($insertID,$n,$option['text'],$option['mediaType'],$option['mediaUrl'],$option['marks']);
                       }
                       break;
                   case 'Fill in the Blank':
                       if(!isset($config['options']))
                           throw new Exception("Match the column options are note provided ");
                       $m = new FillInTheBlankModel();
                       foreach ($config['options'] as $n=>$option){
                           $m->create($insertID,$n,$option['answers'],$option['marks']);
                       }
                       break;
                   case 'Descriptive':
                       if(!isset($config['versions']))
                           throw new Exception("Flip Learning / Descriptive Question versions are required");
                       $m = new DescriptiveQuestionVersionModel();
                       foreach ($config['versions'] as $n=>$option){
                           $m->create($insertID,$n,$option['text']);
                       }
                       break;
                   case 'MCQ':
                   default:
                       if(!isset($config['options']))
                           throw new Exception("options are required fo question type MCQ.");
                       $m = new OptionModel();
                       foreach ($config['options'] as $n=>$option){
                           $m->create($insertID,$n,$option['text'],$option['mediaType'],$option['mediaUrl'],$option['marks']);
                       }
                       break;
               }
               Database::commit();
               return $insertID;
           }
            else{
                new FormException('DB Error: Transaction not working please check database');
            }
        } catch (Exception $e) {
            Database::rollBack();
            new FormException($e->getMessage());
        }
    }

}
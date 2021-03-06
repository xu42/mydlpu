<?php

namespace App\Http\Controllers\Wechat;

use App\Http\Controllers\Controller;
use App\Services\Edu\EduService;
use App\Services\Edu\NewsListService;
use App\Services\LogService;
use App\Services\NetWork\NetworkService;
use App\Services\Weather\WeatherService;
use App\Services\Wechat\MessageNewsService;
use App\Services\Wechat\MessageTextService;
use Cn\Xu42\DlpuEcard\Exception\SystemException;
use Cn\Xu42\DlpuEcard\Service\DlpuEcardService;
use Cn\Xu42\DlpuNetwork\Exception\BaseException;
use Cn\Xu42\Qznjw2014\Account\Exception\LoginException;
use Cn\Xu42\Qznjw2014\Common\Exception\ArgumentException;

class MessageEventClickController extends Controller
{
    public function handle($message, $app)
    {
        switch ($message->EventKey) {
            case config('wechat.button.score_level'):
                return $this->scoreLevel($message, $app);
                break;
            case config('wechat.button.timetable'):
                return $this->timetable($message, $app);
                break;
            case config('wechat.button.news'):
                return $this->news($message, $app);
                break;
            case config('wechat.button.ecard'):
                return $this->eCard($message, $app);
                break;
            case config('wechat.button.network'):
                return $this->network($message, $app);
                break;
            case config('wechat.button.weather'):
                return $this->weather($message, $app);
                break;
            case config('wechat.button.exams'):
                return $this->exams($message, $app);
                break;
            default :
                return 'ing';
                break;
        }

    }


    private function scoreLevel($message, $app)
    {
        $openid = $message->FromUserName;
        $app->staff->message(MessageTextService::ing())->to($openid)->send();
        $eduService = new EduService();
        try {
            $modelUser = $eduService->rowByOpenid($openid);
            if(is_null($modelUser)) throw new ArgumentException();
            $token = $eduService->getToken($modelUser->username, $modelUser->password);
            $scoresLevel = $eduService->getLevelScores($token);
            $news = (new MessageNewsService)->scoreLevel($scoresLevel);
        } catch (ArgumentException $argumentException) {
            $news = MessageTextService::bindingEdu();
        } catch (LoginException $loginException) {
            $news = MessageTextService::bindingEdu();
        } catch (\Throwable $t) {
            LogService::edu('Edu scoreLevel error...', [$openid, $t->getMessage(), $t->getTrace()]);
            $news = MessageTextService::simple($t->getMessage());
        }

        $app->staff->message($news)->to($openid)->send();
    }

    private function timetable($message, $app)
    {
        $openid = $message->FromUserName;
        $app->staff->message(MessageTextService::ing())->to($openid)->send();
        $eduService = new EduService();
        try {
            $modelUser = $eduService->rowByOpenid($openid);
            if (is_null($modelUser)) throw new ArgumentException();
            $token = $eduService->getToken($modelUser->username, $modelUser->password);
            $semester = config('edu.semester');
            $week = $eduService->getCurrentWeek();
            $timetable = @$eduService->getTimetable($token, $semester, $week);
            $news = (new MessageNewsService)->timetable($timetable);
        } catch (ArgumentException $argumentException) {
            $news = MessageTextService::bindingEdu();
        } catch (LoginException $loginException) {
            $news = MessageTextService::bindingEdu();
        } catch (\Throwable $t) {
            LogService::edu('Edu timetable error...', [$openid, $t->getMessage(), $t->getTrace()]);
            $news = MessageTextService::simple($t->getMessage());
        }

        $app->staff->message($news)->to($openid)->send();
    }

    private function news($message, $app)
    {
        $openid = $message->FromUserName;
        $app->staff->message(MessageTextService::ing())->to($openid)->send();
        $newsListService = new NewsListService();
        try {
            $news = MessageNewsService::news($newsListService->currentEvents());
            $app->staff->message($news)->to($openid)->send();
            $news = MessageNewsService::news($newsListService->notice());
            $app->staff->message($news)->to($openid)->send();
            $news = MessageNewsService::news($newsListService->teachingFiles());
            $app->staff->message($news)->to($openid)->send();
        } catch (\Throwable $t) {
            LogService::edu('news currentEvents error...', [$openid, $t->getMessage(), $t->getTrace()]);
            $news = MessageTextService::simple($t->getMessage());
            $app->staff->message($news)->to($openid)->send();
        }
    }

    private function eCard($message, $app)
    {
        $openid = $message->FromUserName;
        $app->staff->message(MessageTextService::ing())->to($openid)->send();
        $eduService = new EduService();
        try {
            $modelUser = $eduService->rowByOpenid($openid);
            if (is_null($modelUser)) throw new SystemException();
            $balance = (new DlpuEcardService)->getBalance($modelUser->username);
            $consumption = (new DlpuEcardService)->getConsumption($modelUser->username);
            $news = (new MessageNewsService)->eCard($balance, $consumption);
        } catch (SystemException $systemException) {
            $news = MessageTextService::bindingEdu();
        } catch (\Throwable $t) {
            LogService::edu('eCard error...', [$openid, $t->getMessage(), $t->getTrace()]);
            $news = MessageTextService::simple($t->getMessage());
        }

        $app->staff->message($news)->to($openid)->send();
    }

    private function network($message, $app)
    {
        $openid = $message->FromUserName;
        $app->staff->message(MessageTextService::ing())->to($openid)->send();
        $networkService = new NetworkService();
        try {
            $modelUser = $networkService->rowByOpenid($openid);
            if (is_null($modelUser)) throw new BaseException();
            $network = $networkService->getByProxy($modelUser->username, $modelUser->password);
            $news = (new MessageNewsService)->network($network);
        } catch (BaseException $baseException) {
            $news = MessageTextService::bindingNet();
        } catch (\Throwable $t) {
            LogService::edu('network error...', [$openid, $t->getMessage(), $t->getTrace()]);
            $news = MessageTextService::simple($t->getMessage());
        }

        $app->staff->message($news)->to($openid)->send();
    }

    private function weather($message, $app)
    {
        $openid = $message->FromUserName;
        $app->staff->message(MessageTextService::ing())->to($openid)->send();
        $weatherService = new WeatherService();

        $news = (new MessageNewsService)->weather($weatherService->get('大连'));

        $app->staff->message($news)->to($openid)->send();
    }

    private function exams($message, $app)
    {
        $openid = $message->FromUserName;
        $app->staff->message(MessageTextService::ing())->to($openid)->send();
        $eduService = new EduService();
        try {
            $modelUser = $eduService->rowByOpenid($openid);
            if(is_null($modelUser)) throw new ArgumentException();
            $semester = config('edu.semester');
            $token = $eduService->getToken($modelUser->username, $modelUser->password);
            $examsInfo = $eduService->getExamsInfo($token, $semester);
            $news = (new MessageNewsService)->examsInfo($examsInfo);
        } catch (ArgumentException $argumentException) {
            $news = MessageTextService::bindingEdu();
        } catch (LoginException $loginException) {
            $news = MessageTextService::bindingEdu();
        } catch (\Throwable $t) {
            LogService::edu('Edu examInfo error...', [$openid, $t->getMessage(), $t->getTrace()]);
            $news = MessageTextService::simple($t->getMessage());
        }

        $app->staff->message($news)->to($openid)->send();
    }
}

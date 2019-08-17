<?php


class Bot {
    private $token = '';

    private $data;

    public function __construct()
    {
        $data = file_get_contents('php://input');
        $data = json_decode($data, true);

        if (!empty($data['message']['chat']['id'])) {
            $this->setData($data);

            $this->checkFirstConnect();
            $this->checkType();
        }
    }

    private function checkFirstConnect()
    {
        $file = $this->getUserId().'.json';
        if(!file_exists($file))
        {
            $this->sendAnswer('Привет '.$this->getUserName().'!');
            $this->setPrivateUserData();
        }
    }

    private function setPrivateUserData($firstName = '', $lastName = '', $age = '')
    {
        $file = $this->getUserId().'.json';

        if(file_exists($file))
        {
            $privateData = $this->getPrivateUserData();
            $data = [
                'firstName' => $firstName ?? $privateData['firstName'],
                'lastName' => $lastName ?? $privateData['lastName'],
                'age' => $age ?? $privateData['age'],
            ];
        }
        else
        {
            $data = [
                'firstName' => $firstName,
                'lastName' => $lastName,
                'age' => $age,
            ];
        }

        file_put_contents($file, json_encode($data));
    }

    private function getPrivateUserData()
    {
        $file = $this->getUserId().'.json';
        $privateData = json_decode(file_get_contents($file), true);

        return [
            'firstName' => $privateData['firstName'] ?? '',
            'lastName' => $privateData['lastName'] ?? '',
            'age' => $privateData['age'] ?? '',
        ];
    }

    private function setData($data)
    {
        $this->data = $data;
    }

    private function getUserId()
    {
        return $this->data['message']['from']['id'];
    }

    private function getUserName()
    {
        return $this->data['message']['from']['first_name'];
    }
    private function getUserMessage()
    {
        return $this->data['message']['text'];
    }

    private function checkType()
    {
        if (!empty($this->data['message']['text'])) {
            $this->startMessageAnswer();
        } else if (!empty($this->data['message']['photo'])) {
            $this->startPhotoAnswer();
        } else if (!empty($this->data['message']['document'])) {
            $this->startDocumentAnswer();
        }
    }

    private function sendAnswer($message)
    {
        $response = array(
            'chat_id' => $this->data['message']['chat']['id'],
            'text' => $message
        );

        $ch = curl_init('https://api.telegram.org/bot' . $this->token . '/sendMessage');
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $response);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_exec($ch);
        curl_close($ch);
    }

    private function startPhotoAnswer()
    {
        $this->sendAnswer('Отличное фото!');
    }
    private function startDocumentAnswer()
    {
        $this->sendAnswer('Отличное фото!');
    }
    private function getPrefixAge($age)
    {
        if(!is_numeric($age))
        {
            return null;
        }

        $lastNum = substr($age, -1);
        switch($lastNum)
        {
            case 0:
            case 5:
            case 6:
            case 7:
            case 8:
            case 9:
                return ' лет';
                break;
            case 1:
                return ' год';
                break;
            case 2:
            case 3:
            case 4:
                return ' года';
                break;

        }
    }

    private function startMessageAnswer()
    {
        $message = $this->getUserMessage();

        if(!empty($message) && mb_strtolower($message) == '/start')
        {
            $this->checkFirstName();
            return;
        }

        if(!empty($message) && mb_strtolower($message) == 'повтори')
        {
            $this->repeatQuestions();
            return;
        }

        $privateUserData = $this->getPrivateUserData();

        if(empty(trim($privateUserData['firstName'])))
        {
            $this->checkFirstName($message);
        }
        else if(empty(trim($privateUserData['lastName'])))
        {
            $this->checkLastName($message);
        }
        else if(empty(trim($privateUserData['age'])))
        {
            $this->checkAge($message);
        }
        else
        {
            $this->finalText();
        }
    }

    private function checkFirstName($message = null)
    {
        if($message)
        {
            $this->setPrivateUserData($message);
            $this->sendAnswer('Какая твоя фамилия?');
        }
        else
        {
            $this->sendAnswer('Какое твоё имя?');
        }
    }
    private function checkLastName($message = null)
    {
        if($message)
        {
            $this->setPrivateUserData(null, $message);
            $this->sendAnswer('Сколько тебе лет?');
        }
        else
        {
            $this->sendAnswer('Какая твоя фамилия?');
        }
    }
    private function checkAge($message = null)
    {
        if($message)
        {
            $this->setPrivateUserData(null, null, $message);
            $this->finalText();
        }
        else
        {
            $this->sendAnswer('Сколько тебе лет?');
        }
    }
    private function finalText()
    {
        $privateUserData = $this->getPrivateUserData();

        $message = 'Привет, '.$privateUserData['firstName'].' '.$privateUserData['lastName'];
        $message .= ', возрастом '.$privateUserData['age'].$this->getPrefixAge($privateUserData['age']).'.'.chr(10);
        $message .= 'Я получил всё, что хотел и ты мне больше не нужен! Пока!!'.chr(10);
        $message .= 'Чтобы попробовать ещё раз, напиши "Повтори"';
        $this->sendAnswer($message);
    }

    private function repeatQuestions()
    {
        $file = $this->getUserId().'.json';
        if(file_exists($file))
        {
            unlink($file);
        }
        $this->setPrivateUserData();

        $this->checkFirstName();
    }
}
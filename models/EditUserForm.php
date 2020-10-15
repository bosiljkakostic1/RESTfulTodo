<?php
namespace app\models;

use Yii;
use yii\base\Model;
use app\models\User;

/**
 * Signup form
 */
class EditUserForm extends Model
{
    public $username;
    public $email;
    public $language;
    public $telefon_number;
    public $oldEmail;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
        ['language', 'string', 'min' => 2, 'max' => 5],
        ['email', 'trim'],
        ['email', 'required'],
        ['email', 'email'],
        ['email', 'string', 'max' => 255],
        ['email', 'validateEmailUnique'],
        ['telefon_number', 'safe'],
    ];
    }

    /**
     * Signs user up.
     *
     * @return bool whether the updateing account was successful
     */
    public function update($username)
    {
        if (!$this->validate()) {
            return null;
        }
    
        $user = User::findByUsername($username);
        $this->oldEmail = $user->email;
        // $user->username = $this->username;
        $user->email = $this->email;
        $user->language = $this->language;
        $user->telefon_number = $this->telefon_number;
        return $user->save();
    }

    public function validateEmailUnique($attribute, $params, $validator)
    {
        if ($this->$attribute != $this->oldEmail) {
            if (User::findByEmail($this->$attribute) != null) {
                $this->addError($attribute, 'The Entered E-Mail is already used !!');
            }
        }
    }
}
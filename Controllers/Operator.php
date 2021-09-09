<?php

/* Welcome to the User controller
 *
 * The user controller works this way:
 * 1. It rubs the lotion on its skin
 * 2. It does as its told and it does it whenever its told
 * 3. If it doesn't rub the lotion, it gets the hose again
 * 4. It puts the lotion in the basket.
 * 5. Put the fucking lotion in the basket !
 */

namespace HexMakina\kadro\Controllers;

use HexMakina\Crudites\Crudites;
use HexMakina\kadro\Auth\{ACL,AccessRefusedException};

class Operator extends \HexMakina\kadro\Controllers\ORM
{

    public function edit()
    {
        parent::edit();

      // do we create? or do we edit someone else ? must be admin
        if (is_null($this->load_model) || $this->operator()->operatorId() !== $this->load_model->operatorId()) {
            $this->authorize('group_admin');
        }
    }

    public function modelClassName(): string
    {
        return "\HexMakina\kadro\Auth\Operator";
    }

    public function save()
    {
        if ($this->operator()->operatorId() !== $this->formModel()->getId()) {
            $this->authorize('group_admin');
        }

        parent::save();
    }

    public function before_save()
    {
      //------------------------------------------------------------- PASSWORDS
        if ($this->formModel()->get('password') != $this->formModel()->get('password_verification')) {
            $this->addError('KADRO_operator_ERR_PASSWORDS_MISMATCH');
            $this->logger()->warning($this->l('KADRO_operator_ERR_PASSWORDS_MISMATCH'));
            $this->edit();
        }
        return $this->errors(); // useless call, errors are managed globally but interface expects it.. refactor needed
    }

    public function dashboard()
    {
        $real_operator_class = get_class($this->operator());
        $this->viewport('users', $real_operator_class::filter([], ['order_by' => [null,'username', 'ASC']]));
    }

    public function destroy()
    {
        $this->change_active();
    }

    public function change_active()
    {
        parent::authorize('group_admin');

        $operator = $this->modelClassName()::one($this->router()->params());
        if ($operator->username() == $this->operator()->username()) {
            throw new AccessRefusedException();
        }

        if ($this->modelClassName()::toggleBoolean($this->modelClassName()::relationalMappingName(), 'active', $operator->operatorId()) === true) {
            $confirmation_message = $operator->isActive() ? 'KADRO_operator_DISABLED' : 'KADRO_operator_ENABLED';
            $this->logger()->notice($this->l($confirmation_message, [$operator->name()]));
        } else {
            $this->logger()->warning($this->l('CRUDITES_ERR_QUERY_FAILED'));
        }

        $this->router()->hopBack();
    }

    public function change_acl()
    {
        parent::authorize('group_admin');

        $operator = $this->modelClassName()::one(['username' => $this->router()->params('username')]);
        if ($operator->username() == $this->operator()->username()) {
            throw new AccessRefusedException();
        }

        $permission_id = $this->router()->params('permission_id');

        $row_data = ['operator_id' => $operator->operatorId(), 'permission_id' => $permission_id];
        $row = ACL::table()->restore($row_data);
        if ($row->isNew()) {
            $row = ACL::table()->produce($row_data);
            $row->persist();
        } else {
            $row->wipe();
        }
        // force reload for permission purposes
        $this->router()->hopBack();
    }
}

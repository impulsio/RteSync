<?php
/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

/* * ***************************Includes********************************* */
require_once dirname(__FILE__).'/../../../../core/php/core.inc.php';

class RteSync extends eqLogic {
    /*
     * Fonction exécutée automatiquement par Jeedom
     */
    public static function cronHourly()
    {
      log::add('RteSync', 'debug', 'Il est '.date('H').'h');
      if (date('H')=='1')
      {
        self::refreshRte();
      }
    }

    public function postInsert()
    {
      $this->addMissingCmdEcoWatt();
    }

    /**
     * Sync all Rte
     * @return none
     */
    public static function refreshRte()
    {
        log::add('RteSync', 'info', 'Synchronisation des API RTE');

        foreach (self::byType('RteSync') as $eqLogic)
        {
          log::add('RteSync', 'debug', 'ID '.$eqLogic->getLogicalId().' - '.$eqLogic->getEqType_name().' - '.$eqLogic->getName());
          self::syncOneRte($eqLogic);
        }
        log::add('RteSync', 'info', __('syncMeross: synchronisation terminée.', __FILE__));
    }

    /**
     * Sync one meross devices.
     * @return none
     */

    public static function syncOneRte($eqLogic)
    {
        log::add('RteSync', 'info', 'SyncOne API RTE : Mise à jour de ' . $eqLogic->getName());
        $auth = $eqLogic->getConfiguration('authentification');
        if (is_null($auth) or $auth == '')
        {
          log::add('RteSync', 'error', 'Merci de renseigner un secret Base 64 pour l\'authentification.');
        }
        else
        {
          log::add('RteSync', 'debug', 'Authentification utilisée : '.$auth);
          $urlOauth = 'https://digital.iservices.rte-france.com/token/oauth';
          $urlEcoWatt = 'https://digital.iservices.rte-france.com/open_api/ecowatt/v4/signals';
          $today= new DateTime("now");

          // use key 'http' even if you send the request to https://...
          $options = array(
            'http' => array(
              'header'  => "Content-type: application/x-www-form-urlencoded\r\nAuthorization: Basic ".$auth."\r\n",
              'method'  => 'POST'
            )
          );
          $context  = stream_context_create($options);

          set_error_handler(
            function ($severity, $message, $file, $line) {
              throw new Exception($message);
            }
          );
          try
          {
            $result = file_get_contents($urlOauth, false, $context);
            if (($result === FALSE) || (!is_json($result)))
            {
              log::add('RteSync', 'error', 'Une erreur est survenue à l\'appel API d\'authentification.');
            }
            else
            {
              log::add('RteSync', 'debug', 'Voici le bearer : '.json_decode($result)->access_token);

              log::add('RteSync', 'debug', 'Appel API ecowatt');
              $options = array(
                'http' => array(
                  'header'  => "Authorization: Bearer ".json_decode($result)->access_token."\r\n",
                  'method'  => 'GET'
                )
              );
              $context  = stream_context_create($options);
              $result = file_get_contents($urlEcoWatt, false, $context);
              if (($result === FALSE) || (!is_json($result)))
              {
                log::add('RteSync', 'error', 'Une erreur est survenue à l\'appel API EcoWatt.');
              }
              else
              {
                $eqLogic->addMissingCmdEcoWatt();
                foreach (json_decode($result)->signals as $signal)
                {
                  log::add('RteSync', 'debug', 'Voici le résultat '.substr($signal->jour,0,10).' : '.$signal->dvalue.' et '.$signal->message);
                  $jour=DateTime::createFromFormat('Y-m-d', substr($signal->jour,0,10));
                  $jourJ=$today->diff($jour)->format("%a");
                  log::add('RteSync', 'debug', 'Jour J+'.$jourJ);
                  $eqLogic->checkAndUpdateCmd('date J+'.$jourJ, substr($signal->jour,0,10));
                  $eqLogic->checkAndUpdateCmd('info J+'.$jourJ, $signal->message);
                  $eqLogic->checkAndUpdateCmd('valeur J+'.$jourJ, $signal->dvalue);
                  $eqLogic->save();
                }
              }
            }
          } catch (Exception $e)
          {
            log::add('RteSync', 'error', 'Exception : '.$e->getMessage());
          }
          restore_error_handler();

          //$eqLogic->save();
          log::add('RteSync', 'info', 'Synchronisation terminée !');
        }

    }

    public function addMissingCmdEcoWatt()
    {
      $cmd = $this->getCmd(null, 'refresh');
      if (!is_object($cmd))
      {
        $cmd = new RteSyncCmd();
        $cmd->setName('Refresh');
        $cmd->setType('action');
        $cmd->setSubType('other');
        $cmd->setTemplate('dashboard', 'default');
        $cmd->setTemplate('mobile', 'default');
        $cmd->setIsVisible(1);
        $cmd->setLogicalId('refresh');
        $cmd->setEqLogic_id($this->getId());
        $cmd->setOrder(0);
        $cmd->save();
      }
      $order=0;
      for ($i = 0; $i <= 3; $i++)
      {
        $order++;
        $cmd = $this->getCmd(null, 'date J+'.$i);
        if (!is_object($cmd))
        {
          $cmd = new RteSyncCmd();
          $cmd->setType('info');
          $cmd->setName('date J+'.$i);
          $cmd->setSubType('string');
          $cmd->setIsVisible(1);
          $cmd->setIsHistorized(0);
          $cmd->setLogicalId('date J+'.$i);
          $cmd->setEqLogic_id($this->getId());
          $cmd->setOrder($order);
          $cmd->save();
        }

        $order++;
        $cmd = $this->getCmd(null, 'valeur J+'.$i);
        if (!is_object($cmd))
        {
          $cmd = new RteSyncCmd();
          $cmd->setType('info');
          $cmd->setName('valeur J+'.$i);
          $cmd->setSubType('numeric');
          $cmd->setIsVisible(1);
          if ($i==0)
          {
            $cmd->setIsHistorized(1);
          }
          else
          {
            $cmd->setIsHistorized(0);
          }
          $cmd->setLogicalId('valeur J+'.$i);
          $cmd->setEqLogic_id($this->getId());
          $cmd->setOrder($order);
          $cmd->save();
        }

        $order++;
        $cmd = $this->getCmd(null, 'info J+'.$i);
        if (!is_object($cmd))
        {
          $cmd = new RteSyncCmd();
          $cmd->setType('info');
          $cmd->setName('info J+'.$i);
          $cmd->setSubType('string');
          $cmd->setIsVisible(1);
          $cmd->setIsHistorized(0);
          $cmd->setLogicalId('info J+'.$i);
          $cmd->setEqLogic_id($this->getId());
          $cmd->setOrder($order);
          $cmd->save();
        }
      }
    }

    public function toHtml($version = 'dashboard')
    {
      $replace = $this->preToHtml($version);
      if (!is_array($replace))
      {
        return $replace;
      }

      $formatterDate = IntlDateFormatter::create(
          'fr_FR',
          IntlDateFormatter::FULL,
          IntlDateFormatter::FULL,
          $tz,
          IntlDateFormatter::GREGORIAN,
          'D MMM'
      );
      $formatterJour = IntlDateFormatter::create(
          'fr_FR',
          IntlDateFormatter::FULL,
          IntlDateFormatter::FULL,
          $tz,
          IntlDateFormatter::GREGORIAN,
          'EEEE'
      );

      for ($i = 0; $i <= 3; $i++)
      {
        $cmd = $this->getCmd(null, 'date J+'.$i);
        $replace['#dateJ'.$i.'#'] = is_object($cmd) ? $cmd->execCmd() : '';
        if (is_object($cmd))
        {
          $jour=DateTime::createFromFormat('Y-m-d', $cmd->execCmd());
          $replace['#jourJ'.$i.'#']=$formatterJour->format($jour);
          $replace['#dateJ'.$i.'#']=$formatterDate->format($jour);
        }


        $cmd = $this->getCmd(null, 'valeur J+'.$i);
        if (is_object($cmd))
        {
          $value=$cmd->execCmd();
          $replace['#valeurJ'.$i.'#'] = $value;
          if ($value=='1')
          {
            $replace['#imageJ'.$i.'#'] = 'courbe-signal-green.png';
          }
          else if ($value)
          {
            $replace['#imageJ'.$i.'#'] = 'courbe-signal-orange.png';
          }
          else if ($value)
          {
            $replace['#imageJ'.$i.'#'] = 'courbe-signal-red.png';
          }
        }
        else
        {
          $replace['#valeurJ'.$i.'#'] ='';
          $replace['#imageJ'.$i.'#'] = '';
        }

        $cmd = $this->getCmd(null, 'info J+'.$i);
        $replace['#infoJ'.$i.'#'] = is_object($cmd) ? $cmd->execCmd() : '';
      }

      $cmd = $this->getCmd(null, 'refresh');
      $replace['#refresh_id#'] = is_object($cmd) ? $cmd->getId() : '';

      $replace['#id#'] = $this->getId();
      //$replace['#uid#'] = $this->getId();
      $replace['#eqLogic_name#'] = $this->getName();
      $replace['#object_name#'] = $this->getObject()->getName();

      $parameters = $this->getDisplay('parameters');
      if (is_array($parameters))
      {
          foreach ($parameters as $key => $value)
          {
              $replace['#' . $key . '#'] = $value;
          }
      }
      $template=getTemplate('core', $version, 'rtesync_ecowatt', 'RteSync');
      return $this->postToHtml($version, template_replace($replace, $template));;
    }

    /**
     * Effacer tous les EqLogic
     * @return none
     */
    public static function deleteAll()
    {
        log::add('RteSync','debug','***** DELETE ALL *****');
        $eqLogics = eqLogic::byType('RteSync');
        foreach ($eqLogics as $eqLogic)
        {
            $eqLogic->remove();
        }
        return array(true, 'OK');
    }

}

class RteSyncCmd extends cmd
{
    public function dontRemoveCmd() {
        return true;
    }

    public function execute($_options = array()) {

        $eqLogic = $this->getEqLogic();
        $action = $this->getLogicalId();
        log::add('RteSync', 'debug', $eqLogic->getLogicalId().' = action: '. $action.' - params '.json_encode($_options) );
        $execute = false;
        // Handle actions like on_x off_x
        $splitAction = explode("_", $action);
        $action = $splitAction[0];
        $channel = $splitAction[1];
        switch ($action) {
            case "refresh":
              log::add('RteSync', 'debug', 'refresh');
              RteSync::syncOneRte($eqLogic);
              break;
            default:
              log::add('RteSync','debug','action: Action='.$action.' '.__('non implementée.', __FILE__));
              break;
        }

    }
}

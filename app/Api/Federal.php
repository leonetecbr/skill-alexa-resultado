<?php

namespace Results\Api;

/**
 * Pega o resultado da Federal de um site
 */
class Federal{
  /**
   * Pega os resultado do site ou do cache
   * @return array
   */
  private static function get(){
    $cached = false;
    $file = __DIR__.'/../../resources/cache/federal.json';
    if (file_exists($file)) {
      $dados = json_decode(file_get_contents($file), true);
      $array = strptime($dados['next'], '%d/%m/%Y');
      $timestamp = mktime(0, 0, 0, $array['tm_mon']+1, $array['tm_mday'], $array['tm_year']+1900);
      if (time()-$timestamp>=0) {
        if ((time()-fileatime($file))<$_ENV['CACHE_TIME']) {
          $cached = true;
        }
      }else{
        $cached = true;
      }
    }
    if (!$cached) {
      $presult = '/\<li\>\n\<div class="nome\-sorteio color"\>[1-5]..\<\/div\>\n\<div class="bg"\>([0-9][0-9][0-9][0-9][0-9]?)\<\/div\>\n<\/li\>/';
      $pdata = '/\<span class="color header\-resultados__datasorteio"\>([0-3][0-9]\/[0-1][0-9]\/2[0-9][0-9][0-9])\<\/span\>/';
      $pnext = '/\<span class="color foother\-resultados__data\-sorteio"\>([0-3][0-9]\/[0-1][0-9]\/2[0-9][0-9][0-9])\<\/span\>/';
      $dado = file_get_contents('https://www.sorteonline.com.br/loteria-federal/resultados');
      preg_match_all($presult, $dado, $resultados);
      preg_match($pdata, $dado, $data);
      preg_match($pnext, $dado, $next);
      $dados['resultados'] = $resultados[1];
      $dados['data'] = $data[1];
      $dados['next'] = $next[1];
      file_put_contents($file, json_encode($dados));
      return $dados;
    }else{
      return $dados;
    }
  }
  
  /**
   * Transforma o resultado em texto para ser lido pela alexa
   * @return string
   */
  public static function getText(){
    $dados = self::get();
    for ($i=0;$i<5; $i++) {
      $dados['resultados'][$i] = substr($dados['resultados'][$i], 1);
      $dados['resultados'][$i] = chunk_split($dados['resultados'][$i], 2, ' ');
    }
    return 'O resultado da Loteria Federal no dia '.$dados['data'].' foi: 1º Prêmio: '.$dados['resultados'][0].', 2º Prêmio: '.$dados['resultados'][1].', 3º Prêmio: '.$dados['resultados'][2].', 4º Prêmio: '.$dados['resultados'][3].', 5º Prêmio: '.$dados['resultados'][4].'. Deseja saber a data do próximo sorteio ou quer que eu repita ?';
  }
  
  /**
   * Transforma o resultado em card para ser mostrado pela alexa
   * @return string
   */
  public static function getCard(){
    $dados = self::get();
    return "1º Prêmio: ".$dados['resultados'][0]."\n2º Prêmio: ".$dados['resultados'][1]."\n3º Prêmio: ".$dados['resultados'][2]."\n4º Prêmio: ".$dados['resultados'][3]."\n5º Prêmio: ".$dados['resultados'][4];
  }
  
  /**
   * Transforma o resultado em texto para ser lido pela alexa
   * @return string
   */
  public static function getNext(){
    $dados = self::get();
    $array = strptime($dados['next'], '%d/%m/%Y');
    $timestamp = mktime(0, 0, 0, $array['tm_mon']+1, $array['tm_mday'], $array['tm_year']+1900);
    $day = date('D', $timestamp);
    switch ($day) {
      case 'Sat':
        $day = 'no sábado';
        break;
      
      case 'Wed':
        $day = 'na quarta-feira';
        break;
      
      default:
        $day = 'em um dia excepcional';
        break;
    }
    return 'O próximo sorteio será '.$day.', dia '.$dados['next'];
  }
}
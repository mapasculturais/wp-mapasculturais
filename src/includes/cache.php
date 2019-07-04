<?php

namespace WPMapasCulturais;

class Cache{
    const MINUTE = 60;
    const HOUR = 60 * self::MINUTE;
    const DAY = 24 * self::HOUR;
    const WEEK = 7 * self::DAY;
    const MONTH = 30 * self::DAY;

    protected $namespace = '';

    public $runtime = [];

    function __construct($namespace) {
        $this->namespace = $namespace;
    }

    /**
     * Verifica se o cache está disponível
     *
     * @param string $key
     * @param bool $verify_persistent verificar cache persistente? default: true
     * @return bool
     */
    function exists(string $key, $verify_persistent = true){
        return $this->existsRuntime($key) || ($verify_persistent && $this->existsPersistent($key));
    }

    /**
     * Verifica se o cache está disponível no runtime cache
     *
     * @param string $key
     * @return bool
     */
    function existsRuntime(string $key){
        return array_key_exists($key, $this->runtime);
    }

    /**
     * Verifica se o cache está disponível no cache persistente
     *
     * @param string $key
     * @param bool $set_runtime_cache_if_found Definir o runtime cache se encontrar o cache
     * @return bool
     */
    function existsPersistent(string $key, $set_runtime_cache_if_found = true){
        $found = false;
        $data = wp_cache_get($key, $this->namespace, $force = false, $found);
        if($found && $set_runtime_cache_if_found){
            $this->addRuntime($key, $data);
        }
        return $found;
    }

    /**
     * Adiciona uma entrada no cache
     *
     * @param string $key
     * @param mixed $data
     * @param int|bool $ttl tempo de vida do cache em segundos. 0 para infinito; false para somente o tempo de execução (runtime) do script
     * @return void
     */
    function add(string $key, $data, $ttl){
        $this->addRuntime($key, $data);

        if($ttl !== false){
            $this->addPersistent($key, $data, $ttl);
        }
    }

    /**
     * Adiciona uma entrada ao runtime cache 
     *
     * @param string $key
     * @param mixed $data
     * @return void
     */
    function addRuntime(string $key, $data){
        $this->runtime[$key] = $data;
    }

    /**
     * Adiciona uma entrada ao persistent cache 
     *
     * @param string $key
     * @param mixed $data
     * @param int $ttl tempo de vida do cache
     * @return void
     */
    function addPersistent(string $key, $data, int $ttl){
        wp_cache_add($key, $data, $this->namespace, $ttl);
    }

    /**
     * Retorna o conteúdo do cache da chave indicada 
     *
     * @param string $key
     * @param bool $verify_persistent verificar e retornar do cache persistente? default: true
     * @return mixed
     */
    function get(string $key, $verify_persistent = true){
        if($this->exists($key, $verify_persistent)){
            return $this->runtime[$key];
        } else {
            return null;
        }
    }

    /**
     * Deleta uma entrada do cache
     *
     * @param string $key
     * @return void
     */
    function delete(string $key){
        unset($this->runtime[$key]);
        wp_cache_delete($key, $this->namespace);
    }

}
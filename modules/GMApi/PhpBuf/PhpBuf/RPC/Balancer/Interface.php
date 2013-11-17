<?php

interface PhpBuf_RPC_Balancer_Interface {
    /**
     * @return PhpBuf_RPC_Socket_Interface
     */
    public function get();
}
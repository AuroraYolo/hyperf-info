<?php
namespace Hyperf\Info\Command;

interface InterfaceCommand
{
    public function show($data);

    public function showMsg($str);
}

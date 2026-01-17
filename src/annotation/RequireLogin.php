<?php
#[\Attribute(\Attribute::TARGET_METHOD | \Attribute::TARGET_CLASS)]
class RequireLogin {
   public const ROLE_USER = 'user';
   public const ROLE_ADMIN = 'admin';

   public array $roles;

   public function __construct(array $roles = [self::ROLE_USER]) // default role is 'user'
   {
      $this->roles = $roles;
   }
}
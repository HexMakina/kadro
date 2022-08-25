START TRANSACTION;
INSERT INTO `kadro_operator` (`id`, `username`, `name`) VALUES (1, 'kadro','kadro');
INSERT INTO `kadro_permission` (`id`, `name`) VALUES (1, 'root'),(2, 'admin'),(3, 'user');
INSERT INTO `kadro_acl` (`operator_id`, `permission_id`) VALUES (1, 1);
COMMIT;

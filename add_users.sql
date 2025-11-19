USE sf_EcoRide;
SET FOREIGN_KEY_CHECKS=0;

INSERT INTO utilisateur (nom, prenom, email, password, roles, is_active, api_token)
VALUES
  ('Admin', 'Super', 'admin@example.com', '$2y$10$G2aU.sS6cciGWwUGU8vjBOv9p82AbkDLGfetHXaTDV1SYSChWqrV2', JSON_ARRAY('ROLE_USER','ROLE_EMPLOYE','ROLE_ADMIN'), 1, 'c027573ebc1123c1eedecafe8b88478dbc72fc62'),
  ('Employe', 'Principal', 'employe@example.com', '$2y$10$z5Gl.ZEvDGmgn0H6JnqwgeCv5uztjHH66Tw6RlckXpJOMSkxEMQIi', JSON_ARRAY('ROLE_USER','ROLE_EMPLOYE'), 1, '65a5a9ae2baf9403f9e1ba9e25a91c9fc7f8a1f3'),
  ('User', 'Simple', 'user@example.com', '$2y$10$MQURbUgw7E0LBXW1PsFWM.4hnUwr3SOQ2mgZAAUr7feYg.g2K/J1e', JSON_ARRAY('ROLE_USER'), 1, '9a7b81422e210b5fb8cf6ca25cb8546c4da2097a');

SET FOREIGN_KEY_CHECKS=1;

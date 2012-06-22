CREATE INDEX `idx_email` ON account(`email`, `password`, `type`);

CREATE INDEX `idx_url` ON account(`url`, `type`);

CREATE INDEX `idx_md5` ON account(`md5_key`);
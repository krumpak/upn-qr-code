WEBSITE := https://upn.primaprodukcija.si

SSH := reseller02.si-shell.net
REMOTE_PATH := /home/primapro/domains/primaprodukcija.si/public_html/upn

Q   := $(shell tput setaf 6)
ERR := $(shell tput setaf 1)
OK  := $(shell tput setaf 2)
HG  := $(shell tput setaf 3)
NC  := $(shell tput sgr0)

ENV := .env
ENV_BAK := env.bak.txt
ENV_EXAMPLE := .env.example
ZIP := _deploy.zip
PORT := 9003
TEST_PORT := 9099

watch:
	@echo "🌐 Starting local server on http://localhost:${PORT}"
	@open "http://localhost:${PORT}"
	php -S localhost:${PORT}

test:
	@echo "$(Q)🚀 Zaganjam testni server na :$(TEST_PORT)...$(NC)"
	@php -S localhost:$(TEST_PORT) > /dev/null 2>&1 & echo $$! > /tmp/upn-test-server.pid; \
	sleep 1; \
	TEST_PORT=$(TEST_PORT) php test/test.php; TEST_EXIT=$$?; \
	kill $$(cat /tmp/upn-test-server.pid) 2>/dev/null; \
	rm -f /tmp/upn-test-server.pid; \
	echo "$(Q)🛑 Ugašam testni server na :$(TEST_PORT)...$(NC)" \
	exit $$TEST_EXIT

install:
	rm -rf ./vendor
	composer install

build:
	@echo "📦 creating '$(ZIP)' file"

	rm -f ./$(ZIP)

	@echo "🧹 delete '.DS_Store'"
	find . -name ".DS_Store" -type f -delete

	@zip -9 -q -r ./$(ZIP) ./ \
		-x "./Makefile" \
			"./README.md" \
			"./.gitignore" \
			"./avg-temp.xlsx" \
			"./.git/*" \
			"./.vscode/*" \
			"./.claude/*" \
			"./test/*" \
			"./_deploy/*"

	@echo "\n$(OK)✅ ZIP file '$(ZIP)' created$(NC)\n"

deploy:
	@ssh $(SSH) '[ -d $(REMOTE_PATH) ]' || { echo "\nremote server path:\n$(HG)\"$(REMOTE_PATH)\"$(NC)\n\n🚫 Path doesn't exist. Deploy cancelled\n"; exit 1; };
	
	@echo "\n--- 📦 Starting build ---\n"
	make build

	@echo "\n--- 📦 Starting remote deploy ---\n";

	@echo "remote server path:\n$(HG)$(REMOTE_PATH)$(NC)\n";
	
	@read -p "${HG}❓ Continue upload on remote server? (y/N) ${NC}" upload; \
	if [ -z "$$upload" ] || ( [ ! "$$upload" = "y" ] && [ ! "$$upload" = "Y" ] ); then echo "\n$(ERR)🚫 deploy canceled$(NC)\n"; exit 1; fi;
	
	@echo "";
	scp ./$(ZIP) $(SSH):$(REMOTE_PATH);
	@echo "\n⬆️ '$(ZIP)' is uploaded"
	
	@echo "\n$(HG)--- 🔗 connecting to remote server ---$(NC)"; sleep 0.25;
	
	@ssh $(SSH) '\
		cd $(REMOTE_PATH); PWD_PATH=$$(pwd); \
		echo ""; ls -la; echo -e "\nchange dir to:\n$(HG)$(REMOTE_PATH)$(NC)\nworking dir:\n$(HG)$$PWD_PATH$(NC)\n"; \
		if [ "$$PWD_PATH" != "$(REMOTE_PATH)" ]; then rm $(ZIP); ls -la; echo -e "\n🚫 Deploy cancelled. Paths mismatch\n"; exit 1; fi; \
		echo -e "$(HG)❓ Continue deploy on remote server? [y/N] $(NC)"; read -p "" yn; \
		if [ "$$yn" != "y" ] && [ "$$yn" != "Y" ]; then rm $(ZIP); ls -la; echo -e "\n🚫 Deploy cancelled\n"; exit 1; fi; \
		echo ""; \
		if [ -f $(ZIP) ]; then echo "⬆️  \"$(ZIP)\" is uploaded"; else echo -e "\n🚫 Deploy cancelled ... \"$(ZIP)\" is missing\n"; exit 1; fi; \
		sleep 0.25; \
		if [ -f $(ENV) ]; then \
			echo "📂 backup \"$(ENV)\" to \"$(ENV_BAK)\""; \
			cp $(ENV) $(ENV_BAK); \
		fi; \
		sleep 0.25; \
		echo "🗜️  unzip \"$(ZIP)\""; \
		unzip -o -q $(ZIP) -d ./; \
		if [ -f $(ENV_BAK) ]; then \
			echo "📂 restore \"$(ENV_BAK)\" to \"$(ENV)\""; \
			mv $(ENV_BAK) $(ENV); \
			rm -f $(ENV_EXAMPLE); \
		fi; \
		sleep 0.25; \
		if [ ! -f $(ENV_BAK) ] && [ ! -f $(ENV) ] && [ -f $(ENV_EXAMPLE) ]; then \
			echo "📂 create \"$(ENV)\" from \"$(ENV_EXAMPLE)\""; \
			mv $(ENV_EXAMPLE) $(ENV); \
		fi; \
		sleep 0.25; \
		echo "🧹 delete \"$(ZIP)\""; \
		rm $(ZIP); \
		sleep 0.25; \
		echo ""; ls -la; \
		echo -e "\n$(OK)--- ✅ Done ---$(NC)\n"'

remote:
	@ssh $(SSH) '\
		cd $(REMOTE_PATH); PWD_PATH=$$(pwd); \
		echo ""; \
		ls -la; \
		echo -e "\nchange dir to:\n$(HG)$(REMOTE_PATH)$(NC)\nworking dir:\n$(HG)$$PWD_PATH$(NC)\n"; \
		sleep 0.25; \
		if [ ! -f $(ENV_BAK) ] && [ ! -f $(ENV) ] && [ -f $(ENV_EXAMPLE) ]; then \
			echo "📂 create \"$(ENV)\" from \"$(ENV_EXAMPLE)\""; \
			mv $(ENV_EXAMPLE) $(ENV); \
		else \
			echo "📂 backup \"$(ENV)\" to \"$(ENV_BAK)\""; \
		fi; \
		sleep 0.25; \
		echo -e "\n$(OK)--- ✅ Done ---$(NC)\n"'

.PHONY: install watch test build deploy remote

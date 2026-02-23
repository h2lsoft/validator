<?php

return [

	"`[FIELD]` is required" => "`[FIELD]` ist erforderlich",
	"`[FIELD]` must be an email address" => "`[FIELD]` muss eine E-Mail-Adresse sein",
	"`[FIELD]` must be in format `[MASK]`" => "`[FIELD]` muss im Format `[MASK]` sein",
	"`[FIELD]` must have a valid option" => "`[FIELD]` muss eine gültige Option haben",
	"`[FIELD]` must not have option `[OPTIONS]`" => "`[FIELD]` darf die Option `[OPTIONS]` nicht haben",
	"`[FIELD]` must be an array" => "`[FIELD]` muss eine Liste sein",
	"`[FIELD]` must be an integer [POSITIVE]" => "`[FIELD]` muss eine [POSITIVE] Ganzzahl sein",
	"`[FIELD]` must be a float [POSITIVE]" => "`[FIELD]` muss eine [POSITIVE] Dezimalzahl sein",

	"`[FIELD]` must be greater than `[MIN]`" => "`[FIELD]` muss größer als `[MIN]` sein",
	"`[FIELD]` must have `[MIN]` choices selected minimum" => "`[FIELD]` muss mindestens `[MIN]` Optionen ausgewählt haben",

	"`[FIELD]` must be lower than `[MAX]`" => "`[FIELD]` muss kleiner als `[MAX]` sein",
	"`[FIELD]` must have `[MAX]` choices selected maximum" => "`[FIELD]` darf höchstens `[MAX]` Optionen ausgewählt haben",

	"`[FIELD]` must be between `[MIN]` and `[MAX]`" => "`[FIELD]` muss zwischen `[MIN]` und `[MAX]` liegen",
	"`[FIELD]` must have choices selected between `[MIN]` and `[MAX]`" => "`[FIELD]` muss zwischen `[MIN]` und `[MAX]` Optionen ausgewählt haben",

	"`[FIELD]` length must be equal to `[LENGTH]`" => "`[FIELD]` muss genau `[LENGTH]` Zeichen lang sein",
	"`[FIELD]` must have `[LENGTH]` choices selected" => "`[FIELD]` muss `[LENGTH]` Optionen ausgewählt haben",

	"`[FIELD]` length must be `[LENGTH]` minimum" => "`[FIELD]` muss mindestens `[LENGTH]` Zeichen lang sein",
	"`[FIELD]` must have `[LENGTH]` choices selected minimum" => "`[FIELD]` muss mindestens `[LENGTH]` Optionen ausgewählt haben",

	"`[FIELD]` length must be `[LENGTH]` character maximum" => "`[FIELD]` darf höchstens `[LENGTH]` Zeichen lang sein",
	"`[FIELD]` must have `[LENGTH]` choices selected maximum" => "`[FIELD]` darf höchstens `[LENGTH]` Optionen ausgewählt haben",

	"`[FIELD]` must be equal to `[VALUE]`" => "`[FIELD]` muss gleich `[VALUE]` sein",
	"`[FIELD]` must be accepted" => "`[FIELD]` muss akzeptiert werden",

	"`[FIELD]` must be a valid url" => "`[FIELD]` muss eine gültige URL sein",
	"`[FIELD]` must contain only alphabetic characters" => "`[FIELD]` darf nur Buchstaben enthalten",
	"`[FIELD]` must contain only alphabetic and numeric characters" => "`[FIELD]` darf nur Buchstaben und Zahlen enthalten",
	"`[FIELD]` must be a valid date in format `[FORMAT]`" => "`[FIELD]` muss ein gültiges Datum im Format `[FORMAT]` sein",
	"`[FIELD]` must be a valid ip address" => "`[FIELD]` muss eine gültige IP-Adresse sein",

	"`[FIELD]` must be equal to `[FIELD_PARENT]`" => "`[FIELD]` muss gleich `[FIELD_PARENT]` sein",

	"`[FIELD]` file is required" => "`[FIELD]` Datei ist erforderlich",
	"`[FIELD]` extension must be `[EXTENSIONS]`" => "`[FIELD]` die Dateierweiterung muss `[EXTENSIONS]` sein",
	"`[FIELD]` file must be lower than `[SIZE]`" => "`[FIELD]` die Datei muss kleiner als `[SIZE]` sein",
	"`[FIELD]` must be `[MIMES]` (not `[FILE_MIME]`)" => "`[FIELD]` muss vom Typ `[MIMES]` sein (nicht `[FILE_MIME]`)",
	"`[FIELD]` is not an uploaded file" => "`[FIELD]` ist keine hochgeladene Datei",

	"`[FIELD]` image width must be equal to `[SIZE]`" => "`[FIELD]` die Bildbreite muss gleich `[SIZE]` sein",
	"`[FIELD]` image width must be lower or equal to `[SIZE]`" => "`[FIELD]` die Bildbreite muss kleiner oder gleich `[SIZE]` sein",
	"`[FIELD]` image height must be equal to `[SIZE]`" => "`[FIELD]` die Bildhöhe muss gleich `[SIZE]` sein",
	"`[FIELD]` image height must be lower or equal to `[SIZE]`" => "`[FIELD]` die Bildhöhe muss kleiner oder gleich `[SIZE]` sein",

	"`[FIELD]` must be a valid IPv4 address" => "`[FIELD]` muss eine gültige IPv4-Adresse sein",
	"`[FIELD]` must be a valid IPv6 address" => "`[FIELD]` muss eine gültige IPv6-Adresse sein",
	"`[FIELD]` must be a valid JSON string" => "`[FIELD]` muss ein gültiger JSON-String sein",
	"`[FIELD]` must be a valid JSON array" => "`[FIELD]` muss ein gültiges JSON-Array sein",
	"`[FIELD]` must be a valid JSON object" => "`[FIELD]` muss ein gültiges JSON-Objekt sein",

	"`[FIELD]` must be a boolean value" => "`[FIELD]` muss ein boolescher Wert sein",
	"`[FIELD]` must be a valid password" => "`[FIELD]` muss ein gültiges Passwort sein",
	"`[FIELD]` must be at least `[LENGTH]` characters" => "`[FIELD]` muss mindestens `[LENGTH]` Zeichen lang sein",
	"`[FIELD]` must contain at least one uppercase letter" => "`[FIELD]` muss mindestens einen Großbuchstaben enthalten",
	"`[FIELD]` must contain at least one digit" => "`[FIELD]` muss mindestens eine Ziffer enthalten",
	"`[FIELD]` must contain at least one special character" => "`[FIELD]` muss mindestens ein Sonderzeichen enthalten",
	"`[FIELD]` must be a valid CSS color" => "`[FIELD]` muss eine gültige CSS-Farbe sein",
	"`[FIELD]` must be a valid MAC address" => "`[FIELD]` muss eine gültige MAC-Adresse sein",
	"`[FIELD]` must be a valid UUID" => "`[FIELD]` muss eine gültige UUID sein",
	"`[FIELD]` must be a valid BIC/SWIFT code" => "`[FIELD]` muss ein gültiger BIC/SWIFT-Code sein",
	"`[FIELD]` must be a valid IBAN" => "`[FIELD]` muss eine gültige IBAN sein",
	"`[FIELD]` must be a valid currency code" => "`[FIELD]` muss ein gültiger Währungscode sein",
	"`[FIELD]` must be a valid language code" => "`[FIELD]` muss ein gültiger Sprachcode sein",
	"`[FIELD]` must be a valid country code" => "`[FIELD]` muss ein gültiger Ländercode sein",
	"`[FIELD]` must be a valid timezone" => "`[FIELD]` muss eine gültige Zeitzone sein",
	"`[FIELD]` must be a valid time in format `[FORMAT]`" => "`[FIELD]` muss eine gültige Uhrzeit im Format `[FORMAT]` sein",
	"`[FIELD]` must be different from `[FIELD_PARENT]`" => "`[FIELD]` muss sich von `[FIELD_PARENT]` unterscheiden",
	"`[FIELD]` must be before `[DATE]`" => "`[FIELD]` muss vor dem `[DATE]` liegen",
	"`[FIELD]` must be after `[DATE]`" => "`[FIELD]` muss nach dem `[DATE]` liegen",
	"Invalid or expired CSRF token" => "Ungültiges oder abgelaufenes CSRF-Token",

];

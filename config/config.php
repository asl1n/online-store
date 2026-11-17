<?php
// Edit these to match your setup
const DB_HOST = '127.0.0.1';
const DB_NAME = 'allure_db';
const DB_USER = 'root';
const DB_PASS = '';

// Folder path of the site in the browser, no trailing slash. '' if at root.
const BASE_URL = '/allure';

const UPLOAD_DIR = __DIR__ . '/../uploads/';
const UPLOAD_URL = BASE_URL . '/uploads/';
const MAX_UPLOAD_MB = 3;

const APP_DEBUG = true; // set false in production

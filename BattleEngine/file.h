#pragma once

void* FileLoad(char* filename, unsigned long* size, char* mode);
int FileSave(char* filename, void* data, unsigned long size);
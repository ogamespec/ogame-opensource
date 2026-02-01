#include <stdio.h>
#include <string.h>
#include <stdlib.h>

// load data from file
void* FileLoad(char* filename, unsigned long* size, char* mode)
{
    FILE* f;
    void* buffer;
    unsigned long     filesize;

    if (size) *size = 0;

    f = fopen(filename, mode);
    if (f == NULL) return NULL;

    fseek(f, 0, SEEK_END);
    filesize = ftell(f);
    fseek(f, 0, SEEK_SET);

    buffer = malloc(filesize + 10);
    if (buffer == NULL)
    {
        fclose(f);
        return NULL;
    }
    memset(buffer, 0, filesize + 10);

    fread(buffer, filesize, 1, f);
    fclose(f);
    if (size) *size = filesize;
    return buffer;
}

// save data in file
int FileSave(char* filename, void* data, unsigned long size)
{
    FILE* f = fopen(filename, "wt");
    if (f == NULL) return -1;

    fwrite(data, size, 1, f);
    fclose(f);
    return 0;
}
#include <stdio.h>
#include <string.h>

int main(int argc, char *argv[])
{
    if (argc != 3)
    {
        printf("Usage: %s <logfile> <keyword>\n", argv[0]);
        return 1;
    }

    FILE *fp = fopen(argv[1], "r");
    if (fp == NULL)
    {
        printf("Error: Could not open file %s\n", argv[1]);
        return 1;
    }

    char line[512];
    int line_number = 0;
    int alert_count = 0;
    char *keyword = argv[2];

    printf("\n[+] Searching for keyword \"%s\" in file \"%s\"...\n\n", keyword, argv[1]);

    while (fgets(line, sizeof(line), fp) != NULL)
    {
        line_number++;

        if (strstr(line, keyword) != NULL)
        {
            alert_count++;

            printf("Alert [%d] - Line %d: %s", alert_count, line_number, line);
        }
    }

    fclose(fp);

    printf("\n----------------------------------------\n");
    printf("[+] Scan completed successfully.\n");
    printf("[+] Total alerts found for \"%s\": %d\n", keyword, alert_count);
    printf("----------------------------------------\n");

    return 0;
}
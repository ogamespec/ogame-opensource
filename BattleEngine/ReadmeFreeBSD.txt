Same as Linux, but replace these statements in battle.h :

#include <linux/types.h>
typedef __u64 u64;

On this :

typedef unsigned long long u64;